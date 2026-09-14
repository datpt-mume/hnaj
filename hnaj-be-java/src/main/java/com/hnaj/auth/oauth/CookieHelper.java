package com.hnaj.auth.oauth;

import jakarta.servlet.http.Cookie;
import jakarta.servlet.http.HttpServletResponse;
import java.time.Duration;
import org.springframework.http.ResponseCookie;

/**
 * Builds the flow cookie with Laravel-matching attributes:
 * name hnaj_google_oauth_flow, path /api/auth/google, HttpOnly, SameSite=Lax, host-only.
 */
public final class CookieHelper {
    public static final String FLOW_COOKIE = "hnaj_google_oauth_flow";
    public static final String FLOW_COOKIE_PATH = "/api/auth/google";

    private CookieHelper() {
    }

    public static ResponseCookie flowCookie(String value, Duration maxAge) {
        return ResponseCookie.from(FLOW_COOKIE, value)
                .path(FLOW_COOKIE_PATH)
                .maxAge(maxAge)
                .httpOnly(true)
                .sameSite("Lax")
                .secure(false)
                .build();
    }

    public static ResponseCookie clearFlowCookie() {
        return ResponseCookie.from(FLOW_COOKIE, "")
                .path(FLOW_COOKIE_PATH)
                .maxAge(Duration.ZERO)
                .httpOnly(true)
                .sameSite("Lax")
                .secure(false)
                .build();
    }
}