package com.hnaj.auth.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.auth.ResendEmailVerificationService;
import com.hnaj.auth.UserView;
import com.hnaj.auth.VerifyEmailService;
import com.hnaj.auth.validation.AuthInput;
import java.util.Map;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class EmailVerificationController {
    private final VerifyEmailService verify;
    private final ResendEmailVerificationService resend;

    public EmailVerificationController(
            VerifyEmailService verify,
            ResendEmailVerificationService resend) {
        this.verify = verify;
        this.resend = resend;
    }

    @PostMapping("/api/auth/email/verify")
    public ResponseEntity<Map<String, Object>> verify(@RequestBody(required = false) Map<String, Object> body) {
        AuthInput input = new AuthInput(body);
        String token = input.required("token", 255, false, false);
        input.validate();

        UserView user = verify.verify(token);

        return ApiResponse.success(Map.of("user", user),
                "Email verified successfully. You can sign in now.", null);
    }

    @PostMapping("/api/auth/email/resend")
    public ResponseEntity<Map<String, Object>> resend(@RequestBody(required = false) Map<String, Object> body) {
        AuthInput input = new AuthInput(body);
        String email = input.email();
        input.validate();

        resend.resend(email);

        return ApiResponse.success(null,
                "If the email address needs verification, a new link has been sent.", null);
    }
}