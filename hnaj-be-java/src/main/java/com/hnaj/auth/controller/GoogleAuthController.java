package com.hnaj.auth.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.api.exception.AuthFlowException;
import com.hnaj.auth.AuthResult;
import com.hnaj.auth.config.HnajProperties;
import com.hnaj.auth.oauth.CookieHelper;
import com.hnaj.auth.oauth.GoogleAuthFlowService;
import com.hnaj.auth.security.TokenCodec;
import jakarta.servlet.http.HttpServletRequest;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.Map;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.http.ResponseCookie;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

/**
 * Google OAuth endpoints (knowledge-base 03-domain-rules §2). Bearer tokens never
 * appear in URLs; the callback redirects to the SPA with a one-time exchange code.
 */
@RestController
@RequestMapping("/api/auth/google")
public class GoogleAuthController {
    private final GoogleAuthFlowService flow;
    private final HnajProperties properties;
    private final TokenCodec codec;

    public GoogleAuthController(GoogleAuthFlowService flow, HnajProperties properties, TokenCodec codec) {
        this.flow = flow;
        this.properties = properties;
        this.codec = codec;
    }

    @GetMapping("/redirect")
    public ResponseEntity<Map<String, Object>> redirect(HttpServletRequest request) {
        String flowCookie = codec.generate();
        String authorizationUrl = flow.startFlow(flowCookie);
        ResponseEntity<Map<String, Object>> body = ApiResponse.success(
                Map.of("authorization_url", authorizationUrl),
                "Google authorization URL created successfully.",
                null);
        return withCookie(body, CookieHelper.flowCookie(flowCookie, Duration.ofMinutes(5)));
    }

    @GetMapping("/callback")
    public ResponseEntity<Void> callback(HttpServletRequest request) {
        String state = request.getParameter("state");
        String code = request.getParameter("code");
        String error = request.getParameter("error");

        String flowCookie = cookieValue(request);

        // Validation parity with GoogleCallbackRequest: malformed input redirects to
        // the SPA with GOOGLE_AUTH_FAILED and clears the flow cookie (never a JSON 422).
        if (state == null || state.isBlank() || state.length() > 255
                || (code == null || code.isBlank())
                || (error != null && !error.isBlank())
                || (code != null && error != null)) {
            return errorRedirect();
        }

        if (error != null && !error.isEmpty()) {
            return errorRedirect();
        }

        try {
            String exchangeCode = flow.handleCallback(code, state, flowCookie);
            return redirectWithCookie(properties.getFrontend().getBaseUrl()
                    + "/auth/google/callback?code=" + encode(exchangeCode), null);
        } catch (AuthFlowException exception) {
            return errorRedirect(exception.getErrorCode().name());
        }
    }

    @PostMapping("/exchange")
    public ResponseEntity<Map<String, Object>> exchange(
            HttpServletRequest request,
            @RequestBody(required = false) Map<String, Object> body) {
        String code = body == null ? null : (body.get("code") instanceof String s ? s : null);
        if (code == null || code.isBlank() || code.length() > 255) {
            return clearCookie(ApiResponse.error(
                    null, Map.of("code", java.util.List.of("The code field is required.")),
                    "VALIDATION_ERROR", HttpStatus.UNPROCESSABLE_ENTITY));
        }
        String flowCookie = cookieValue(request);
        try {
            AuthResult result = flow.exchange(code, flowCookie);
            ResponseEntity<Map<String, Object>> response = ApiResponse.success(
                    Map.of("user", result.user(), "token", result.token()),
                    "Signed in with Google successfully.",
                    null);
            return clearCookie(response);
        } catch (AuthFlowException exception) {
            ResponseEntity<Map<String, Object>> response = ApiResponse.error(
                    exception.getMessage(), null, exception.getErrorCode().name(),
                    HttpStatus.valueOf(exception.getStatus()));
            return clearCookie(response);
        }
    }

    private String cookieValue(HttpServletRequest request) {
        jakarta.servlet.http.Cookie[] cookies = request.getCookies();
        if (cookies == null) return null;
        for (jakarta.servlet.http.Cookie cookie : cookies) {
            if (CookieHelper.FLOW_COOKIE.equals(cookie.getName())) {
                return cookie.getValue();
            }
        }
        return null;
    }

    private ResponseEntity<Map<String, Object>> withCookie(
            ResponseEntity<Map<String, Object>> response, ResponseCookie cookie) {
        HttpHeaders headers = new HttpHeaders();
        headers.add(HttpHeaders.SET_COOKIE, cookie.toString());
        return ResponseEntity.status(response.getStatusCode())
                .headers(headers)
                .body(response.getBody());
    }

    private ResponseEntity<Map<String, Object>> clearCookie(ResponseEntity<Map<String, Object>> response) {
        return withCookie(response, CookieHelper.clearFlowCookie());
    }

    private ResponseEntity<Void> redirectWithCookie(String location, ResponseCookie cookie) {
        HttpHeaders headers = new HttpHeaders();
        headers.add(HttpHeaders.LOCATION, location);
        if (cookie != null) {
            headers.add(HttpHeaders.SET_COOKIE, cookie.toString());
        }
        return ResponseEntity.status(HttpStatus.FOUND).headers(headers).build();
    }

    private ResponseEntity<Void> errorRedirect() {
        return errorRedirect("GOOGLE_AUTH_FAILED");
    }

    private ResponseEntity<Void> errorRedirect(String errorCode) {
        return redirectWithCookie(properties.getFrontend().getBaseUrl()
                + "/auth/google/callback?error=" + encode(errorCode),
                CookieHelper.clearFlowCookie());
    }

    private String encode(String value) {
        return URLEncoder.encode(value, StandardCharsets.UTF_8);
    }
}