package com.hnaj.auth;

import com.hnaj.api.exception.AuthFlowException;
import com.hnaj.auth.entity.User;
import com.hnaj.auth.repository.UserRepository;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;

/**
 * Shared credential + account-state checks for regular and admin sign-in.
 * Mirrors Laravel AuthenticateCredentials (order matters and is contract-tested):
 * credentials → active → email verified.
 */
@Service
public class AuthenticateCredentialsService {
    private static final String DUMMY_HASH =
            "$2y$12$Rz7Yq4gK5jFQZ2oJ1uV3pOgnZxJ8Kf1lQ6wYbT0sN9eXhH2mC4uCa";

    private final UserRepository users;
    private final PasswordEncoder passwordEncoder;

    public AuthenticateCredentialsService(UserRepository users, PasswordEncoder passwordEncoder) {
        this.users = users;
        this.passwordEncoder = passwordEncoder;
    }

    public User authenticate(String username, String password) {
        User user = users.findByUsername(username).orElse(null);

        boolean passwordMatches = passwordEncoder.matches(
                password, user == null ? DUMMY_HASH : user.getPassword());

        if (user == null || !passwordMatches) {
            throw AuthFlowException.invalidCredentials();
        }
        if (!"active".equals(user.getStatus())) {
            throw AuthFlowException.accountNotActive();
        }
        if (user.getEmailVerifiedAt() == null) {
            throw AuthFlowException.emailNotVerified();
        }
        return user;
    }
}
