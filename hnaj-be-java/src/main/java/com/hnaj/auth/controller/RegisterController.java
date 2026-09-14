package com.hnaj.auth.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.auth.UserRegistrationService;
import com.hnaj.auth.UserView;
import com.hnaj.auth.validation.AuthInput;
import java.util.Map;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class RegisterController {
    private final UserRegistrationService registration;

    public RegisterController(UserRegistrationService registration) {
        this.registration = registration;
    }

    @PostMapping("/api/auth/register")
    public ResponseEntity<Map<String, Object>> register(@RequestBody(required = false) Map<String, Object> body) {
        AuthInput input = new AuthInput(body);
        String username = input.username(true);
        String fullName = input.required("full_name", 255, true, false);
        String email = input.email();
        String password = input.password(true, true);
        input.validate();

        UserView user = registration.register(username, fullName, email, password);

        Map<String, Object> data = Map.of("user", user);
        // status 201 like Laravel RegisterController (ApiResponse::success accepts status).
        return ApiResponse.success(data,
                "Registration completed. Please check your email to verify your address.",
                null, HttpStatus.CREATED);
    }
}