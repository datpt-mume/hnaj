package com.hnaj.auth.mail;

import com.hnaj.auth.entity.User;

/** Outbound auth emails. Plaintext tokens appear only here, never in logs from callers. */
public interface AuthMailer {
    void sendVerificationEmail(User user, String verificationUrl, int expiresInHours);

    void sendAccountSetupEmail(User user, String setupUrl, int expiresInHours);
}
