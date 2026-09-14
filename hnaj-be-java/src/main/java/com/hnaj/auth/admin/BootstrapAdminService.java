package com.hnaj.auth.admin;

import com.hnaj.auth.entity.User;
import com.hnaj.auth.entity.UserRole;
import com.hnaj.auth.repository.RoleRepository;
import com.hnaj.auth.repository.UserRepository;
import com.hnaj.auth.repository.UserRoleRepository;
import com.hnaj.auth.security.TokenCodec;
import com.hnaj.auth.time.TimeConfiguration;
import java.time.Clock;
import java.time.LocalDateTime;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.support.TransactionTemplate;

/**
 * One-time system administrator bootstrap, mirroring Laravel CreateAdminAccount
 * (non-HTTP). Only the first admin may be created; later calls are rejected.
 */
@Service
public class BootstrapAdminService {
    private final TransactionTemplate transactions;
    private final UserRepository users;
    private final RoleRepository roles;
    private final UserRoleRepository userRoles;
    private final PasswordEncoder passwordEncoder;
    private final Clock clock;
    private final TokenCodec codec;

    public BootstrapAdminService(
            TransactionTemplate transactions,
            UserRepository users,
            RoleRepository roles,
            UserRoleRepository userRoles,
            PasswordEncoder passwordEncoder,
            Clock clock,
            TokenCodec codec) {
        this.transactions = transactions;
        this.users = users;
        this.roles = roles;
        this.userRoles = userRoles;
        this.passwordEncoder = passwordEncoder;
        this.clock = clock;
        this.codec = codec;
    }

    public boolean adminIsCreated() {
        return userRoles.existsByRoleName("admin");
    }

    /**
     * @throws IllegalStateException when an admin already exists or the identity is taken.
     */
    public void create(String username, String fullName, String email, String password) {
        String normalizedUsername = username.trim().toLowerCase(java.util.Locale.ROOT);
        String normalizedEmail = email.trim().toLowerCase(java.util.Locale.ROOT);
        String normalizedFullName = fullName.trim();

        transactions.executeWithoutResult(status -> {
            var adminRole = roles.findByNameForUpdate("admin")
                    .orElseThrow(() -> new IllegalStateException("Admin role is missing."));
            boolean already = userRoles.existsByRoleName("admin");
            if (already) {
                throw new IllegalStateException("The system administrator has already been created.");
            }
            if (users.existsByUsername(normalizedUsername)) {
                throw new IllegalStateException("The username is already in use.");
            }
            if (users.existsByEmail(normalizedEmail)) {
                throw new IllegalStateException("The email address is already in use.");
            }

            LocalDateTime now = TimeConfiguration.now(clock);
            User admin = new User();
            admin.setName(normalizedFullName);
            admin.setUsername(normalizedUsername);
            admin.setEmail(normalizedEmail);
            admin.setPassword(passwordEncoder.encode(password));
            admin.setStatus("active");
            admin.setEmailVerifiedAt(now);
            admin.setCreatedAt(now);
            admin.setUpdatedAt(now);
            User saved = users.saveAndFlush(admin);

            UserRole assignment = new UserRole();
            assignment.setUser(saved);
            assignment.setRole(adminRole);
            assignment.setAssignedAt(now);
            assignment.setCreatedAt(now);
            assignment.setUpdatedAt(now);
            userRoles.save(assignment);
        });
    }
}