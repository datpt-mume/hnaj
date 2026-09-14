package com.hnaj.auth.admin;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.ApplicationArguments;
import org.springframework.boot.ApplicationRunner;
import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.stereotype.Component;

/**
 * Optional one-time admin bootstrap. Never enabled by default; the operator starts the
 * application with HNAJ_BOOTSTRAP_ADMIN=true and supplies the identity via environment
 * variables (values are never logged or written to source control).
 */
@Component
@ConditionalOnProperty(name = "hnaj.bootstrap-admin.enabled", havingValue = "true")
public class BootstrapAdminRunner implements ApplicationRunner {
    private static final Logger log = LoggerFactory.getLogger(BootstrapAdminRunner.class);

    private final BootstrapAdminService service;
    private final String username;
    private final String fullName;
    private final String email;
    private final String password;

    public BootstrapAdminRunner(
            BootstrapAdminService service,
            @Value("${HNAJ_ADMIN_USERNAME:}") String username,
            @Value("${HNAJ_ADMIN_FULL_NAME:Administrator}") String fullName,
            @Value("${HNAJ_ADMIN_EMAIL:}") String email,
            @Value("${HNAJ_ADMIN_PASSWORD:}") String password) {
        this.service = service;
        this.username = username;
        this.fullName = fullName;
        this.email = email;
        this.password = password;
    }

    @Override
    public void run(ApplicationArguments args) {
        if (username.isBlank() || email.isBlank() || password.isBlank()) {
            log.warn("HNAJ_BOOTSTRAP_ADMIN=true nhưng thiếu HNAJ_ADMIN_USERNAME/EMAIL/PASSWORD; bỏ qua.");
            return;
        }
        if (service.adminIsCreated()) {
            log.info("Admin đã tồn tại; bỏ qua bootstrap.");
            return;
        }
        service.create(username, fullName, email, password);
        log.info("Đã tạo tài khoản admin bootstrap (username={})", username);
    }
}