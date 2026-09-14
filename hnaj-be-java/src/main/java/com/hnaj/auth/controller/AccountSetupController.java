package com.hnaj.auth.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.auth.AccountSetupService;
import com.hnaj.auth.UserView;
import com.hnaj.auth.validation.AuthInput;
import java.util.Map;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class AccountSetupController {
    private final AccountSetupService setup;

    public AccountSetupController(AccountSetupService setup) {
        this.setup = setup;
    }

    @PostMapping("/api/auth/account/setup")
    public ResponseEntity<Map<String, Object>> complete(@RequestBody(required = false) Map<String, Object> body) {
        AuthInput input = new AuthInput(body);
        String token = input.required("token", 0, false, false);
        // Setup password: min 8 + confirmed, no letters/numbers rule (Laravel AccountSetupRequest).
        String password = input.password(true, false);
        input.validate();

        UserView user = setup.complete(token, password);

        return ApiResponse.success(Map.of("user", user),
                "Tài khoản đã được kích hoạt. Bạn có thể đăng nhập.", null);
    }
}