package com.hnaj.auth;

import com.hnaj.api.exception.AuthFlowException;
import com.hnaj.auth.entity.User;
import com.hnaj.auth.mail.AuthMailer;
import com.hnaj.auth.repository.UserRepository;
import com.hnaj.auth.time.TimeConfiguration;
import com.hnaj.auth.validation.AuthValidationException;
import java.time.Clock;
import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * POST /api/auth/register — Laravel {@code RegisterUser}: active/unverified user + role + mail.
 * Mail failure rolls the whole transaction back so no orphan account remains.
 */
@Service
public class UserRegistrationService {
    private final UserRepository users;
    private final AuthRoleService roleService;
    private final PasswordEncoder passwordEncoder;
    private final AuthMailer mailer;
    private final EmailVerificationTokenService verificationTokens;
    private final Clock clock;
    private final FrontendUrlBuilder urls;

    public UserRegistrationService(
            UserRepository users,
            AuthRoleService roleService,
            PasswordEncoder passwordEncoder,
            AuthMailer mailer,
            EmailVerificationTokenService verificationTokens,
            Clock clock,
            FrontendUrlBuilder urls) {
        this.users = users;
        this.roleService = roleService;
        this.passwordEncoder = passwordEncoder;
        this.mailer = mailer;
        this.verificationTokens = verificationTokens;
        this.clock = clock;
        this.urls = urls;
    }

    /** @throws AuthValidationException on duplicate username/email (422 like Laravel unique rule). */
    @Transactional
    public UserView register(String username, String fullName, String email, String password) {
        if (users.existsByUsername(username)) {
            throw new AuthValidationException(Map.of("username",
                    List.of("The username has already been taken.")));
        }
        if (users.existsByEmail(email)) {
            throw new AuthValidationException(
                    Map.of("email", List.of("The email has already been taken.")));
        }

        LocalDateTime now = TimeConfiguration.now(clock);
        User user = new User();
        user.setName(fullName);
        user.setUsername(username);
        user.setEmail(email);
        user.setPassword(passwordEncoder.encode(password));
        user.setStatus("active");
        user.setCreatedAt(now);
        user.setUpdatedAt(now);
        try {
            users.saveAndFlush(user);
        } catch (DataIntegrityViolationException duplicate) {
            throw uniqueConstraintValidation(duplicate.getMessage());
        }

        roleService.assign(user, AuthRoleService.ROLE_USER);
        verificationTokens.issue(user);

        return UserView.from(user, roleService.namesOf(user.getId()));
    }

    private AuthValidationException uniqueConstraintValidation(String message) {
        String constraint = message == null ? "" : message.toLowerCase();
        if (constraint.contains("username")) {
            return new AuthValidationException(Map.of("username",
                    List.of("The username has already been taken.")));
        }
        return new AuthValidationException(
                Map.of("email", List.of("The email has already been taken.")));
    }
}
