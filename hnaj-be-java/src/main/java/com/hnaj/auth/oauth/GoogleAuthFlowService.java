package com.hnaj.auth.oauth;

import com.github.benmanes.caffeine.cache.Cache;
import com.hnaj.api.exception.AuthFlowException;
import com.hnaj.auth.AccessTokenService;
import com.hnaj.auth.AuthResult;
import com.hnaj.auth.AuthRoleService;
import com.hnaj.auth.UserView;
import com.hnaj.auth.entity.Role;
import com.hnaj.auth.entity.User;
import com.hnaj.auth.oauth.OAuthCacheConfiguration.ExchangePayload;
import com.hnaj.auth.repository.RoleRepository;
import com.hnaj.auth.repository.UserRepository;
import com.hnaj.auth.security.TokenCodec;
import com.hnaj.auth.time.TimeConfiguration;
import java.time.Clock;
import java.time.LocalDateTime;
import java.util.Locale;
import java.util.Map;
import java.util.NoSuchElementException;
import java.util.concurrent.ThreadLocalRandom;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * Orchestrates the three-step Google flow and stores OAuth state/codes with
 * Laravel-matching TTLs in Caffeine: state 300s, exchange code 60s. Each step
 * consumes state/code atomically so it cannot be replayed.
 */
@Service
public class GoogleAuthFlowService {
    private final GoogleOAuthClient google;
    private final UserRepository users;
    private final RoleRepository roles;
    private final UsernameGenerator usernameGenerator;
    private final AccessTokenService accessTokens;
    private final AuthRoleService roleService;
    private final TokenCodec codec;
    private final Cache<String, String> stateCache;
    private final Cache<String, ExchangePayload> exchangeCache;
    private final org.springframework.transaction.support.TransactionTemplate transactionTemplate;
    private final Clock clock;
    private static final String STATE_PREFIX = "auth:google:state:";
    private static final String EXCHANGE_PREFIX = "auth:google:exchange:";

    public GoogleAuthFlowService(
            GoogleOAuthClient google,
            UserRepository users,
            RoleRepository roles,
            UsernameGenerator usernameGenerator,
            AccessTokenService accessTokens,
            AuthRoleService roleService,
            TokenCodec codec,
            @Qualifier("googleStateCache") Cache<String, String> stateCache,
            @Qualifier("googleExchangeCache") Cache<String, ExchangePayload> exchangeCache,
            org.springframework.transaction.support.TransactionTemplate transactionTemplate,
            Clock clock) {
        this.google = google;
        this.users = users;
        this.roles = roles;
        this.usernameGenerator = usernameGenerator;
        this.accessTokens = accessTokens;
        this.roleService = roleService;
        this.codec = codec;
        this.stateCache = stateCache;
        this.exchangeCache = exchangeCache;
        this.transactionTemplate = transactionTemplate;
        this.clock = clock;
    }

    public String startFlow(String flowCookie) {
        if (!google.isConfigured()) {
            throw AuthFlowException.googleAuthFailed("Google sign-in is not configured.");
        }
        String state = randomToken();
        stateCache.put(stateCacheKey(state), flowHash(flowCookie));
        return google.buildAuthorizationUrl(state);
    }

    public String handleCallback(String code, String state, String flowCookie) {
        if (flowCookie == null || flowCookie.isEmpty()) {
            throw AuthFlowException.googleAuthFailed("The Google sign-in session has expired.");
        }
        String storedFlowHash = stateCache.getIfPresent(stateCacheKey(state));
        if (storedFlowHash == null) {
            throw AuthFlowException.googleAuthFailed("The Google sign-in session has expired.");
        }
        // Consume the state once regardless of the cookie match (parity with Cache::pull).
        stateCache.invalidate(stateCacheKey(state));
        if (!constantTimeEquals(storedFlowHash, flowHash(flowCookie))) {
            throw AuthFlowException.googleAuthFailed("The Google sign-in session has expired.");
        }

        GoogleProfile profile = google.fetchProfile(code);
        if (!profile.emailVerified()) {
            throw AuthFlowException.googleAuthFailed("This Google account has an unverified email address.");
        }
        User user = resolveUser(profile);
        if (!isActive(user)) {
            throw AuthFlowException.accountNotActive();
        }
        return issueExchangeCode(user, storedFlowHash);
    }

    @Transactional
    public AuthResult exchange(String exchangeCode, String flowCookie) {
        if (flowCookie == null || flowCookie.isEmpty()) {
            throw AuthFlowException.googleAuthFailed("This Google sign-in session is invalid or has expired.");
        }
        ExchangePayload payload = exchangeCache.getIfPresent(exchangeCacheKey(exchangeCode));
        if (payload == null) {
            throw AuthFlowException.googleAuthFailed("This Google sign-in code is invalid or has expired.");
        }
        // Consume once; even a wrong cookie burns the code (parity with Cache::pull).
        exchangeCache.invalidate(exchangeCacheKey(exchangeCode));
        if (!constantTimeEquals(payload.flowHash(), flowHash(flowCookie))) {
            throw AuthFlowException.googleAuthFailed("This Google sign-in code is invalid or has expired.");
        }

        User user = users.findById(payload.userId())
                .orElseThrow(AuthFlowException::googleAuthFailed);
        if (!isActive(user)) {
            throw AuthFlowException.accountNotActive();
        }
        if (!roleService.hasRole(user.getId(), AuthRoleService.ROLE_USER)) {
            throw AuthFlowException.forbiddenRole();
        }
        String token = accessTokens.issue(user, "spa");
        return new AuthResult(UserView.from(user, roleService.namesOf(user.getId())), token);
    }

    /** Resolve by google_id, then email; create a new account otherwise. */
    private User resolveUser(GoogleProfile profile) {
        User byGoogleId = users.findByGoogleId(profile.googleId()).orElse(null);
        if (byGoogleId != null) {
            requireUserRole(byGoogleId);
            byGoogleId.setAvatarUrl(profile.avatarUrl());
            return users.save(byGoogleId);
        }

        User byEmail = users.findByEmail(profile.email()).orElse(null);
        if (byEmail != null) {
            requireUserRole(byEmail);
            if (byEmail.getGoogleId() != null
                    && !byEmail.getGoogleId().equals(profile.googleId())) {
                throw AuthFlowException.googleAuthFailed(
                        "This email address is already linked to another Google account.");
            }
            byEmail.setGoogleId(profile.googleId());
            byEmail.setAvatarUrl(profile.avatarUrl() != null ? profile.avatarUrl() : byEmail.getAvatarUrl());
            if (byEmail.getEmailVerifiedAt() == null) {
                byEmail.setEmailVerifiedAt(TimeConfiguration.now(clock));
            }
            return users.save(byEmail);
        }
        return createUser(profile);
    }

    private User createUser(GoogleProfile profile) {
        int maxRetries = 3;
        for (int attempt = 0; attempt <= maxRetries; attempt++) {
            try {
                int currentAttempt = attempt;
                return transactionTemplate.execute(status -> {
                    User user = new User();
                    user.setName(profile.name());
                    user.setUsername(currentAttempt == 0
                            ? usernameGenerator.fromEmail(profile.email())
                            : usernameGenerator.generateNew(profile.email()));
                    user.setEmail(profile.email());
                    user.setPassword(randomToken()); // Google-only; never disclosed
                    user.setStatus("active");
                    user.setGoogleId(profile.googleId());
                    user.setAvatarUrl(profile.avatarUrl());
                    user.setEmailVerifiedAt(TimeConfiguration.now(clock));
                    user.setCreatedAt(TimeConfiguration.now(clock));
                    user.setUpdatedAt(TimeConfiguration.now(clock));
                    User saved = users.saveAndFlush(user);
                    roleService.assign(saved, AuthRoleService.ROLE_USER);
                    return saved;
                });
            } catch (DataIntegrityViolationException duplicate) {
                if (attempt == maxRetries) {
                    throw AuthFlowException.googleAuthFailed(
                            "Unable to complete Google sign-in. Please try again.");
                }
                String message = duplicate.getMessage() == null ? "" : duplicate.getMessage().toLowerCase();
                if (!message.contains("username")) {
                    throw duplicate;
                }
            }
        }
        throw AuthFlowException.googleAuthFailed("Unable to complete Google sign-in. Please try again.");
    }

    private String issueExchangeCode(User user, String flowHash) {
        String code = randomToken();
        exchangeCache.put(exchangeCacheKey(code), new ExchangePayload(user.getId(), flowHash));
        return code;
    }

    private void requireUserRole(User user) {
        if (!roleService.hasRole(user.getId(), AuthRoleService.ROLE_USER)) {
            throw AuthFlowException.forbiddenRole();
        }
    }

    private boolean isActive(User user) {
        return "active".equals(user.getStatus());
    }

    private String stateCacheKey(String state) {
        return STATE_PREFIX + codec.hash(state);
    }

    private String exchangeCacheKey(String code) {
        return EXCHANGE_PREFIX + codec.hash(code);
    }

    private String flowHash(String flowCookie) {
        return codec.hash(flowCookie);
    }

    private String randomToken() {
        return codec.generate();
    }

    private boolean constantTimeEquals(String a, String b) {
        return java.security.MessageDigest.isEqual(
                a.getBytes(java.nio.charset.StandardCharsets.UTF_8),
                b.getBytes(java.nio.charset.StandardCharsets.UTF_8));
    }
}