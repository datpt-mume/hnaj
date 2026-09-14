package com.hnaj.auth.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.auth.AuthResult;
import com.hnaj.auth.LoginUserService;
import com.hnaj.auth.validation.AuthInput;
import java.util.Map;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class LoginController {
    private final LoginUserService login;

    public LoginController(LoginUserService login) {
        this.login = login;
    }

    @PostMapping("/api/auth/login")
    public ResponseEntity<Map<String, Object>> login(@RequestBody(required = false) Map<String, Object> body) {
        AuthInput input = new AuthInput(body);
        String username = input.username(false);
        String password = input.required("password", 0, false, false);
        input.validate();

        AuthResult result = login.login(username, password);

        return ApiResponse.success(Map.of("user", result.user(), "token", result.token()),
                "Signed in successfully.", null);
    }
}