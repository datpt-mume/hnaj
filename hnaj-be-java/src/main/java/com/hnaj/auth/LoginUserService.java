package com.hnaj.auth;

import com.hnaj.api.exception.AuthFlowException;
import com.hnaj.auth.entity.User;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * Regular user sign-in (role user OR sub_admin). Token name mirrors Sanctum 'spa'.
 */
@Service
public class LoginUserService {
    private final AuthenticateCredentialsService authenticate;
    private final AuthRoleService roles;
    private final AccessTokenService accessTokens;

    public LoginUserService(
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

        if (!roles.hasAnyRole(user.getId(), AuthRoleService.ROLE_USER, AuthRoleService.ROLE_SUB_ADMIN)) {
            throw AuthFlowException.forbiddenRole();
        }

        String token = accessTokens.issue(user, "spa");
        return new AuthResult(UserView.from(user, roles.namesOf(user.getId())), token);
    }
}
