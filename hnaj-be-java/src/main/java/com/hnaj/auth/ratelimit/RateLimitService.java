package com.hnaj.auth.ratelimit;

import com.github.benmanes.caffeine.cache.Cache;
import com.github.benmanes.caffeine.cache.Caffeine;
import io.github.bucket4j.Bandwidth;
import io.github.bucket4j.Bucket;
import io.github.bucket4j.Refill;
import jakarta.servlet.http.HttpServletRequest;
import java.time.Duration;
import java.util.HashMap;
import java.util.Map;
import org.springframework.http.HttpMethod;
import org.springframework.stereotype.Service;

/**
 * Per-route rate limits mirroring Laravel {@code throttle:N,1} (routes/api.php).
 * Buckets are keyed by client IP (Laravel throttle key: user id if authenticated,
 * otherwise IP); auth endpoints are public, so IP is the effective key.
 */
@Service
public class RateLimitService {
    private final Cache<Key, Bucket> buckets = Caffeine.newBuilder()
            .maximumSize(10_000)
            .expireAfterAccess(Duration.ofMinutes(15))
            .build();

    private final Map<String, Rule> rules = new HashMap<>();

    public RateLimitService() {
        // POST /api/auth/*
        rules.put(key(HttpMethod.POST, "/api/auth/register"), new Rule(10, Duration.ofMinutes(1)));
        rules.put(key(HttpMethod.POST, "/api/auth/login"), new Rule(5, Duration.ofMinutes(1)));
        rules.put(key(HttpMethod.POST, "/api/auth/email/verify"), new Rule(10, Duration.ofMinutes(1)));
        rules.put(key(HttpMethod.POST, "/api/auth/email/resend"), new Rule(5, Duration.ofMinutes(1)));
        rules.put(key(HttpMethod.POST, "/api/auth/account/setup"), new Rule(10, Duration.ofMinutes(1)));
        rules.put(key(HttpMethod.POST, "/api/auth/google/exchange"), new Rule(10, Duration.ofMinutes(1)));
        // GET /api/auth/google/*
        rules.put(key(HttpMethod.GET, "/api/auth/google/redirect"), new Rule(10, Duration.ofMinutes(1)));
        rules.put(key(HttpMethod.GET, "/api/auth/google/callback"), new Rule(10, Duration.ofMinutes(1)));
        // Admin sign-in
        rules.put(key(HttpMethod.POST, "/api/admin/auth/login"), new Rule(5, Duration.ofMinutes(1)));
        // Public metadata
        rules.put(key(HttpMethod.GET, "/api/meta/discovery"), new Rule(60, Duration.ofMinutes(1)));
    }

    /** @return true if the request is allowed. */
    public boolean tryConsume(HttpServletRequest request) {
        Rule rule = rules.get(key(request.getMethod(), request.getRequestURI()));
        if (rule == null) {
            return true;
        }
        Key bucketKey = new Key(rule, clientKey(request));
        Bucket bucket = buckets.get(bucketKey, ignored -> Bucket.builder()
                .addLimit(Bandwidth.classic(rule.capacity(), Refill.greedy(rule.capacity(), rule.window())))
                .build());
        return bucket.tryConsume(1);
    }

    /** Clears all live buckets; used by tests to isolate cases sharing one singleton. */
    public void clearBuckets() {
        buckets.invalidateAll();
    }

    private String clientKey(HttpServletRequest request) {
        String forwarded = request.getHeader("X-Forwarded-For");
        if (forwarded != null && !forwarded.isBlank()) {
            return forwarded.split(",")[0].trim();
        }
        return request.getRemoteAddr() == null ? "unknown" : request.getRemoteAddr();
    }

    private String key(HttpMethod method, String path) {
        return method.name() + " " + path;
    }

    private static String key(String method, String uri) {
        return method + " " + uri;
    }

    private record Rule(int capacity, Duration window) {
    }

    private record Key(Rule rule, String client) {
    }
}