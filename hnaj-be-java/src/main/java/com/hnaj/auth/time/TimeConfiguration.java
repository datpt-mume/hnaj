package com.hnaj.auth.time;

import java.time.Clock;
import java.time.Instant;
import java.time.LocalDateTime;
import java.time.ZoneOffset;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

/** Single source of truth for the current time so tests can shift the clock. */
@Configuration
public class TimeConfiguration {

    @Bean
    public Clock utcClock() {
        return Clock.systemUTC();
    }

    public static LocalDateTime now(Clock clock) {
        return LocalDateTime.ofInstant(clock.instant(), ZoneOffset.UTC);
    }
}