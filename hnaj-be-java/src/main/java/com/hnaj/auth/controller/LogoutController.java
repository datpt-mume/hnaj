package com.hnaj.auth.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.auth.AccessTokenService;
import com.hnaj.auth.AuthenticatedUser;
import java.util.Map;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;

/** POST /api/auth/logout — bearer only, no role middleware (routes/api.php line 101). */
@RestController
public class LogoutController {
    private final AccessTokenService accessTokens;

    public LogoutController(AccessTokenService accessTokens) {
        this.accessTokens = accessTokens;
    }

    @PostMapping("/api/auth/logout")
    public ResponseEntity<Map<String, Object>> logout(AuthenticatedUser principal) {
        accessTokens.revoke(principal.rawToken());
        return ApiResponse.success(null, "Signed out successfully.", null);
    }
}