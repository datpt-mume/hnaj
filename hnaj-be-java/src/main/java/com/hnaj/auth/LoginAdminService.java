package com.hnaj.auth;

import com.hnaj.api.exception.AuthFlowException;
import com.hnaj.auth.entity.User;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/** Admin sign-in via the dedicated admin login endpoint (role admin required). */
@Service
public class LoginAdminService {
    private final AuthenticateCredentialsService authenticate;
    private final AuthRoleService roles;
    private final AccessTokenService accessTokens;

    public LoginAdminService(
            AuthenticateCredentialsService authenticate,
            AuthRoleService roles,
            AccessTokenService accessTokens) {
        this.authenticate = authenticate;
        this.roles = roles;
        this.accessTokens = accessTokens;
    }

    @Transactional
    public AuthResult login(String username, String password) {
        User user = authenticate.authenticate(username, password);

        if (!roles.hasRole(user.getId(), AuthRoleService.ROLE_ADMIN)) {
            throw AuthFlowException.forbiddenRole();
        }

        String token = accessTokens.issue(user, "admin");
        return new AuthResult(UserView.from(user, roles.namesOf(user.getId())), token);
    }
}