package com.hnaj.auth.ratelimit;

import com.hnaj.auth.ratelimit.RateLimitService;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;
import org.springframework.http.HttpHeaders;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;

/** Applies per-route buckets; a rejected request gets the shared 429 envelope. */
@Component
public class RateLimitFilter extends OncePerRequestFilter {
    private final RateLimitService rateLimitService;
    private final com.hnaj.auth.security.SecurityErrorWriter errorWriter;

    public RateLimitFilter(
            RateLimitService rateLimitService,
            com.hnaj.auth.security.SecurityErrorWriter errorWriter) {
        this.rateLimitService = rateLimitService;
        this.errorWriter = errorWriter;
    }

    @Override
    protected void doFilterInternal(
            HttpServletRequest request,
            HttpServletResponse response,
            FilterChain chain) throws ServletException, IOException {
        if (rateLimitService.tryConsume(request)) {
            chain.doFilter(request, response);
            return;
        }
        response.setHeader(HttpHeaders.RETRY_AFTER, "60");
        errorWriter.write(response, org.springframework.http.HttpStatus.TOO_MANY_REQUESTS,
                "TOO_MANY_REQUESTS", "Too Many Attempts.");
    }
}