package com.hnaj.auth;

import com.hnaj.auth.ratelimit.RateLimitService;
import jakarta.servlet.http.HttpServletRequest;
import org.junit.jupiter.api.Test;
import org.mockito.Mockito;

import static org.assertj.core.api.Assertions.assertThat;
import static org.mockito.Mockito.when;

class RateLimitServiceTest {
    private final RateLimitService service = new RateLimitService();

    private HttpServletRequest request(String method, String path, String ip) {
        HttpServletRequest req = Mockito.mock(HttpServletRequest.class);
        when(req.getMethod()).thenReturn(method);
        when(req.getRequestURI()).thenReturn(path);
        when(req.getRemoteAddr()).thenReturn(ip);
        when(req.getHeader("X-Forwarded-For")).thenReturn(null);
        return req;
    }

    @Test
    void loginAllowsFiveThenRejects() {
        HttpServletRequest req = request("POST", "/api/auth/login", "1.2.3.4");
        for (int i = 0; i < 5; i++) {
            assertThat(service.tryConsume(req)).as("attempt %d", i + 1).isTrue();
        }
        assertThat(service.tryConsume(req)).isFalse();
    }

    @Test
    void separateClientsHaveSeparateBuckets() {
        HttpServletRequest clientA = request("POST", "/api/auth/login", "1.2.3.4");
        HttpServletRequest clientB = request("POST", "/api/auth/login", "5.6.7.8");
        for (int i = 0; i < 5; i++) {
            service.tryConsume(clientA);
        }
        assertThat(service.tryConsume(clientA)).isFalse();
        assertThat(service.tryConsume(clientB)).isTrue();
    }

    @Test
    void unconfiguredPathAlwaysAllowed() {
        HttpServletRequest req = request("GET", "/api/test", "1.2.3.4");
        for (int i = 0; i < 100; i++) {
            assertThat(service.tryConsume(req)).isTrue();
        }
    }

    @Test
    void registerAllowsTen() {
        HttpServletRequest req = request("POST", "/api/auth/register", "9.9.9.9");
        for (int i = 0; i < 10; i++) {
            assertThat(service.tryConsume(req)).isTrue();
        }
        assertThat(service.tryConsume(req)).isFalse();
    }
}