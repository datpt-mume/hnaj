package com.hnaj.auth.mail;

import com.hnaj.auth.entity.User;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.context.annotation.Profile;
import org.springframework.stereotype.Component;
import org.thymeleaf.context.Context;
import org.thymeleaf.spring6.SpringTemplateEngine;

/**
 * Dev/log delivery mirroring Laravel MAIL_MAILER=log (knowledge-base 04-auth-security).
 * Real SMTP delivery stays out of scope; this keeps transactional send/fail semantics testable.
 */
@Component
@Profile("!smtp")
public class LoggingAuthMailer implements AuthMailer {
    private static final Logger log = LoggerFactory.getLogger(LoggingAuthMailer.class);

    private final SpringTemplateEngine templates;

    public LoggingAuthMailer(@Qualifier("textMailTemplateEngine") SpringTemplateEngine templates) {
        this.templates = templates;
    }

    @Override
    public void sendVerificationEmail(User user, String verificationUrl, int expiresInHours) {
        Context context = new Context();
        context.setVariable("user", user);
        context.setVariable("verificationUrl", verificationUrl);
        context.setVariable("expiresInHours", expiresInHours);
        render("auth/verify-email", context, user.getEmail(), "Xác thực email để hoàn tất đăng ký HNAJ");
    }

    @Override
    public void sendAccountSetupEmail(User user, String setupUrl, int expiresInHours) {
        Context context = new Context();
        context.setVariable("user", user);
        context.setVariable("setupUrl", setupUrl);
        context.setVariable("expiresInHours", expiresInHours);
        render("auth/account-setup", context, user.getEmail(), "Kích hoạt tài khoản quản lý địa điểm HNAJ");
    }

    private void render(String template, Context context, String recipient, String subject) {
        String text = templates.process(template, context);
        // Dev parity with Laravel log driver; never log plaintext verification URLs in production.
        log.info("MAIL to={} subject={} body-length={}", recipient, subject, text.length());
    }
}
