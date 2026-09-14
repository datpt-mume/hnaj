package com.hnaj.auth;

import com.hnaj.auth.entity.EmailVerificationToken;
import com.hnaj.auth.entity.User;
import com.hnaj.auth.mail.AuthMailer;
import com.hnaj.auth.repository.EmailVerificationTokenRepository;
import com.hnaj.auth.security.TokenCodec;
import com.hnaj.auth.time.TimeConfiguration;
import java.time.Clock;
import java.time.LocalDateTime;
import org.springframework.stereotype.Service;

/**
 * Issues a single-use email verification token (24h) and delivers the mail.
 * Mirrors Laravel IssueEmailVerificationToken; callers own their transaction scope.
 */
@Service
public class EmailVerificationTokenService {
    public static final int EXPIRES_IN_HOURS = 24;

    private final EmailVerificationTokenRepository tokens;
    private final TokenCodec codec;
    private final AuthMailer mailer;
    private final FrontendUrlBuilder urls;
    private final Clock clock;

    public EmailVerificationTokenService(
            EmailVerificationTokenRepository tokens,
            TokenCodec codec,
            AuthMailer mailer,
            FrontendUrlBuilder urls,
            Clock clock) {
        this.tokens = tokens;
        this.codec = codec;
        this.mailer = mailer;
        this.urls = urls;
        this.clock = clock;
    }

    public void issue(User user) {
        LocalDateTime now = TimeConfiguration.now(clock);
        tokens.invalidateActiveTokens(user, now);

        String plainToken = codec.generate();
        EmailVerificationToken row = new EmailVerificationToken();
        row.setUser(user);
        row.setTokenHash(codec.hash(plainToken));
        row.setExpiresAt(now.plusHours(EXPIRES_IN_HOURS));
        row.setCreatedAt(now);
        tokens.save(row);

        mailer.sendVerificationEmail(user, urls.verifyEmailUrl(plainToken), EXPIRES_IN_HOURS);
    }
}
