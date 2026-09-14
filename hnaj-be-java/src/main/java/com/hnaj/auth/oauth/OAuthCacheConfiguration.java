package com.hnaj.auth.oauth;

import com.github.benmanes.caffeine.cache.Cache;
import com.github.benmanes.caffeine.cache.Caffeine;
import java.time.Duration;
import java.util.concurrent.ConcurrentHashMap;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

/** In-memory OAuth caches with Laravel-matching TTLs and atomic consume. */
@Configuration
public class OAuthCacheConfiguration {
    public static final Duration STATE_TTL = Duration.ofSeconds(300);
    public static final Duration EXCHANGE_TTL = Duration.ofSeconds(60);
    public static final int MAX_ENTRIES = 1000;

    @Bean(name = "googleStateCache")
    public Cache<String, String> googleStateCache() {
        // state key -> SHA-256 flow cookie hash
        return Caffeine.newBuilder()
                .maximumSize(MAX_ENTRIES)
                .expireAfterWrite(STATE_TTL)
                .build();
    }

    @Bean(name = "googleExchangeCache")
    public Cache<String, ExchangePayload> googleExchangeCache() {
        return Caffeine.newBuilder()
                .maximumSize(MAX_ENTRIES)
                .expireAfterWrite(EXCHANGE_TTL)
                .build();
    }

    public record ExchangePayload(Long userId, String flowHash) {
    }
}