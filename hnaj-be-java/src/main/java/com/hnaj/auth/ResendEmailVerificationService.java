package com.hnaj.auth;

import com.hnaj.auth.entity.User;
import com.hnaj.auth.repository.UserRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * POST /api/auth/email/resend — deliberately neutral for unknown/verified/inactive
 * accounts so responses never reveal whether an email is registered.
 */
@Service
public class ResendEmailVerificationService {
    private final UserRepository users;
    private final EmailVerificationTokenService issueToken;

    public ResendEmailVerificationService(UserRepository users, EmailVerificationTokenService issueToken) {
        this.users = users;
        this.issueToken = issueToken;
    }

    /** Invalidation + new token must be atomic (Laravel runs the action in a transaction). */
    @Transactional
    public void resend(String email) {
        User user = users.findByEmail(email).orElse(null);
        if (user == null) {
            return;
        }
        if (user.getEmailVerifiedAt() != null || !"active".equals(user.getStatus())) {
            return;
        }
        issueToken.issue(user);
    }
}
