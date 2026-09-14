package com.hnaj.auth;

import com.hnaj.api.exception.AuthFlowException;
import com.hnaj.auth.entity.AccountSetupToken;
import com.hnaj.auth.entity.User;
import com.hnaj.auth.repository.AccountSetupTokenRepository;
import com.hnaj.auth.repository.UserRepository;
import com.hnaj.auth.security.TokenCodec;
import com.hnaj.auth.time.TimeConfiguration;
import java.time.Clock;
import java.time.LocalDateTime;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * POST /api/auth/account/setup — Sub-admin activation: set password, verify email,
 * consume token. Laravel semantics: status is NOT changed and no bearer token is issued.
 */
@Service
public class AccountSetupService {
    private final AccountSetupTokenRepository tokens;
    private final UserRepository users;
    private final AuthRoleService roles;
    private final TokenCodec codec;
    private final PasswordEncoder passwordEncoder;
    private final Clock clock;

    public AccountSetupService(
            AccountSetupTokenRepository tokens,
            UserRepository users,
            AuthRoleService roles,
            TokenCodec codec,
            PasswordEncoder passwordEncoder,
            Clock clock) {
        this.tokens = tokens;
        this.users = users;
        this.roles = roles;
        this.codec = codec;
        this.passwordEncoder = passwordEncoder;
        this.clock = clock;
    }

    @Transactional
    public UserView complete(String plainToken, String newPassword) {
        LocalDateTime now = TimeConfiguration.now(clock);
        AccountSetupToken token = tokens
                .lockUsableByTokenHash(codec.hash(plainToken), now)
                .orElseThrow(AuthFlowException::invalidVerificationToken);

        User user = token.getUser();
        token.setUsedAt(now);
        tokens.save(token);

        user.setPassword(passwordEncoder.encode(newPassword));
        user.setEmailVerifiedAt(now);
        user.setUpdatedAt(now);
        users.save(user);

        return UserView.from(user, roles.namesOf(user.getId()));
    }
}
