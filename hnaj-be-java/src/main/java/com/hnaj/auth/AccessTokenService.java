package com.hnaj.auth;

import com.hnaj.auth.entity.AccessToken;
import com.hnaj.auth.config.HnajProperties;
import com.hnaj.auth.entity.User;
import com.hnaj.auth.repository.AccessTokenRepository;
import com.hnaj.auth.security.TokenCodec;
import com.hnaj.auth.time.TimeConfiguration;
import java.time.Clock;
import java.time.LocalDateTime;
import java.util.Optional;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/** Issues opaque bearer tokens in HNAJ's own format (Q17), separate from Sanctum. */
@Service
public class AccessTokenService {
    private final AccessTokenRepository tokens;
    private final TokenCodec codec;
    private final Clock clock;
    private final HnajProperties properties;

    public AccessTokenService(
            AccessTokenRepository tokens,
            TokenCodec codec,
            Clock clock,
            HnajProperties properties) {
        this.tokens = tokens;
        this.codec = codec;
        this.clock = clock;
        this.properties = properties;
    }

    /** Generates a plaintext token and persists its SHA-256 hash; returns the plaintext once. */
    @Transactional
    public String issue(User user, String name) {
        String plaintext = codec.generate();
        LocalDateTime now = TimeConfiguration.now(clock);
        AccessToken row = new AccessToken();
        row.setUser(user);
        row.setTokenHash(codec.hash(plaintext));
        row.setName(name);
        int expiryHours = properties.getAuth().getTokenExpiresInHours();
        row.setExpiresAt(expiryHours > 0 ? now.plusHours(expiryHours) : null);
        row.setLastUsedAt(null);
        row.setCreatedAt(now);
        row.setUpdatedAt(now);
        tokens.save(row);
        return plaintext;
    }

    /** Finds a non-expired token by its plaintext and loads the owning user. */
    @Transactional(readOnly = true)
    public Optional<AccessToken> resolve(String plaintext) {
        if (plaintext == null || plaintext.isBlank()) {
            return Optional.empty();
        }
        LocalDateTime now = TimeConfiguration.now(clock);
        return tokens.findByTokenHashAndUserStatusAndUserDeletedAtIsNull(codec.hash(plaintext), "active")
                .filter(t -> t.getExpiresAt() == null || t.getExpiresAt().isAfter(now));
    }

    /** Revokes the exact token used for this request (logout). */
    @Transactional
    public void revoke(String plaintext) {
        if (plaintext == null || plaintext.isBlank()) {
            return;
        }
        tokens.deleteByTokenHash(codec.hash(plaintext));
    }
}