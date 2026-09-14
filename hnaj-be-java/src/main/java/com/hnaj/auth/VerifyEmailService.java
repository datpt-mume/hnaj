package com.hnaj.auth;

import com.hnaj.api.exception.AuthFlowException;
import com.hnaj.auth.entity.EmailVerificationToken;
import com.hnaj.auth.entity.User;
import com.hnaj.auth.repository.EmailVerificationTokenRepository;
import com.hnaj.auth.repository.UserRepository;
import com.hnaj.auth.security.TokenCodec;
import com.hnaj.auth.time.TimeConfiguration;
import java.time.Clock;
import java.time.LocalDateTime;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * POST /api/auth/email/verify — one-time token with pessimistic lock.
 * Already-verified returns 409 and does NOT consume the token (Laravel VerifyEmail).
 */
@Service
public class VerifyEmailService {
    private final EmailVerificationTokenRepository tokens;
    private final UserRepository users;
    private final AuthRoleService roles;
    private final TokenCodec codec;
    private final Clock clock;

    public VerifyEmailService(
            EmailVerificationTokenRepository tokens,
            UserRepository users,
            AuthRoleService roles,
            TokenCodec codec,
            Clock clock) {
        this.tokens = tokens;
        this.users = users;
        this.roles = roles;
        this.codec = codec;
        this.clock = clock;
    }

    @Transactional
    public UserView verify(String plainToken) {
        LocalDateTime now = TimeConfiguration.now(clock);
        EmailVerificationToken token = tokens
                .lockUsableByTokenHash(codec.hash(plainToken), now)
                .orElseThrow(AuthFlowException::invalidVerificationToken);

        User user = token.getUser();
        if (user.getEmailVerifiedAt() != null) {
            throw AuthFlowException.emailAlreadyVerified();
        }

        token.setUsedAt(now);
        tokens.save(token);

        user.setEmailVerifiedAt(now);
        user.setUpdatedAt(now);
        users.save(user);

        return UserView.from(user, roles.namesOf(user.getId()));
    }
}
