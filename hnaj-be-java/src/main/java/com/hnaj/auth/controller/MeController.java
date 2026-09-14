package com.hnaj.auth.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.auth.AuthenticatedUser;
import com.hnaj.auth.AuthRoleService;
import com.hnaj.auth.UserView;
import com.hnaj.auth.security.RequireRole;
import java.util.Map;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class MeController {
    private final AuthRoleService roleService;

    public MeController(AuthRoleService roleService) {
        this.roleService = roleService;
    }

    @GetMapping("/api/auth/me")
    @RequireRole({"user", "sub_admin"})
    public ResponseEntity<Map<String, Object>> me(AuthenticatedUser principal) {
        UserView view = UserView.from(principal.user(),
                roleService.namesOf(principal.user().getId()));
        return ApiResponse.success(Map.of("user", view),
                "Authenticated user loaded successfully.", null);
    }
}