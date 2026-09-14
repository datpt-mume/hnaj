package com.hnaj.auth.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.auth.AuthenticatedUser;
import com.hnaj.auth.UpdateProfileService;
import com.hnaj.auth.UserView;
import com.hnaj.auth.security.RequireRole;
import com.hnaj.auth.validation.AuthInput;
import java.util.Map;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PatchMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class UpdateProfileController {
    private final UpdateProfileService profile;

    public UpdateProfileController(UpdateProfileService profile) {
        this.profile = profile;
    }

    @PatchMapping("/api/auth/me")
    @RequireRole({"user", "sub_admin"})
    public ResponseEntity<Map<String, Object>> update(
            AuthenticatedUser principal,
            @RequestBody(required = false) Map<String, Object> body) {
        AuthInput input = new AuthInput(body);
        String fullName = input.required("full_name", 255, true, false);
        input.validate();

        UserView user = profile.update(principal.user(), fullName);

        return ApiResponse.success(Map.of("user", user),
                "Profile updated successfully.", null);
    }
}