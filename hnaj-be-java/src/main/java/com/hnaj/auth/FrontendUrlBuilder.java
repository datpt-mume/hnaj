package com.hnaj.auth;

import com.hnaj.auth.config.HnajProperties;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import org.springframework.stereotype.Component;

/** Builds frontend deep-link URLs that carry one-time plaintext tokens. */
@Component
public class FrontendUrlBuilder {
    private final HnajProperties properties;

    public FrontendUrlBuilder(HnajProperties properties) {
        this.properties = properties;
    }

    public String verifyEmailUrl(String plainToken) {
        return properties.getFrontend().getBaseUrl()
                + "/verify-email?token=" + encode(plainToken);
    }

    public String accountSetupUrl(String plainToken) {
        return properties.getFrontend().getBaseUrl()
                + "/account-setup?token=" + encode(plainToken);
    }

    private String encode(String value) {
        return URLEncoder.encode(value, StandardCharsets.UTF_8);
    }
}
