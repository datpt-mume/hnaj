package com.hnaj.auth.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.auth.AuthResult;
import com.hnaj.auth.AuthenticatedUser;
import com.hnaj.auth.LoginAdminService;
import com.hnaj.auth.UserView;
import com.hnaj.auth.AccessTokenService;
import com.hnaj.auth.security.RequireRole;
import com.hnaj.auth.validation.AuthInput;
import java.util.Map;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class AdminAuthController {
    private final LoginAdminService login;
    private final AccessTokenService accessTokens;
    private final com.hnaj.auth.AuthRoleService roleService;

    public AdminAuthController(
            LoginAdminService login,
            AccessTokenService accessTokens,
            com.hnaj.auth.AuthRoleService roleService) {
        this.login = login;
        this.accessTokens = accessTokens;
        this.roleService = roleService;
    }

    @PostMapping("/api/admin/auth/login")
    public ResponseEntity<Map<String, Object>> login(@RequestBody(required = false) Map<String, Object> body) {
        AuthInput input = new AuthInput(body);
        String username = input.username(false);
        String password = input.required("password", 0, false, false);
        input.validate();

        AuthResult result = login.login(username, password);

        return ApiResponse.success(Map.of("user", result.user(), "token", result.token()),
                "Signed in to the admin area successfully.", null);
    }

    @GetMapping("/api/admin/auth/me")
    @RequireRole("admin")
    public ResponseEntity<Map<String, Object>> me(AuthenticatedUser principal) {
        UserView view = UserView.from(principal.user(),
                roleService.namesOf(principal.user().getId()));
        return ApiResponse.success(Map.of("user", view),
                "Authenticated user loaded successfully.", null);
    }

    @PostMapping("/api/admin/auth/logout")
    @RequireRole("admin")
    public ResponseEntity<Map<String, Object>> logout(AuthenticatedUser principal) {
        accessTokens.revoke(principal.rawToken());
        return ApiResponse.success(null, "Signed out successfully.", null);
    }
}