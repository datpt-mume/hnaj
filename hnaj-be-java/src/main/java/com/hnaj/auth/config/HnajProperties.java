package com.hnaj.auth.config;

import org.springframework.boot.context.properties.ConfigurationProperties;
import org.springframework.validation.annotation.Validated;
import jakarta.validation.constraints.NotBlank;

/** hnaj.* application properties used by auth flows. */
@Validated
@ConfigurationProperties(prefix = "hnaj")
public class HnajProperties {
    private final Frontend frontend = new Frontend();
    private final Auth auth = new Auth();

    public Frontend getFrontend() { return frontend; }
    public Auth getAuth() { return auth; }

    public static class Frontend {
        @NotBlank
        private String baseUrl = "http://localhost:8082";

        public String getBaseUrl() { return baseUrl; }
        public void setBaseUrl(String baseUrl) { this.baseUrl = baseUrl; }
    }

    public static class Auth {
        private int tokenExpiresInHours = 24;

        public int getTokenExpiresInHours() { return tokenExpiresInHours; }
        public void setTokenExpiresInHours(int tokenExpiresInHours) { this.tokenExpiresInHours = tokenExpiresInHours; }
    }
}
