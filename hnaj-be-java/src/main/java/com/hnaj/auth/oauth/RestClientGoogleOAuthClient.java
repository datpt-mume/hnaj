package com.hnaj.auth.oauth;

import com.hnaj.api.exception.AuthFlowException;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Component;
import org.springframework.util.LinkedMultiValueMap;
import org.springframework.util.MultiValueMap;
import org.springframework.web.client.RestClient;

/**
 * Real Google OAuth client using Spring RestClient with a 10s timeout (Laravel parity).
 * The endpoint behavior (JSON authorization_url, cookie) lives in GoogleAuthController.
 */
@Component
public class RestClientGoogleOAuthClient implements GoogleOAuthClient {
    private static final String AUTHORIZATION_URL = "https://accounts.google.com/o/oauth2/v2/auth";
    private static final String TOKEN_URL = "https://oauth2.googleapis.com/token";
    private static final String USERINFO_URL = "https://www.googleapis.com/oauth2/v3/userinfo";

    private final String clientId;
    private final String clientSecret;
    private final String redirectUri;
    private final RestClient restClient;

    public RestClientGoogleOAuthClient(
            @Value("${GOOGLE_CLIENT_ID:}") String clientId,
            @Value("${GOOGLE_CLIENT_SECRET:}") String clientSecret,
            @Value("${GOOGLE_REDIRECT_URI:}") String redirectUri,
            RestClient.Builder builder) {
        this.clientId = clientId == null ? "" : clientId.trim();
        this.clientSecret = clientSecret == null ? "" : clientSecret.trim();
        this.redirectUri = redirectUri == null ? "" : redirectUri.trim();
        this.restClient = builder.build();
    }

    @Override
    public boolean isConfigured() {
        return !clientId.isEmpty() && !clientSecret.isEmpty() && !redirectUri.isEmpty();
    }

    @Override
    public String buildAuthorizationUrl(String state) {
        MultiValueMap<String, String> params = new LinkedMultiValueMap<>();
        params.add("client_id", clientId);
        params.add("redirect_uri", redirectUri);
        params.add("response_type", "code");
        params.add("scope", "openid email profile");
        params.add("state", state);
        params.add("access_type", "online");
        params.add("prompt", "select_account");
        StringBuilder sb = new StringBuilder(AUTHORIZATION_URL).append('?');
        int i = 0;
        for (var entry : params.entrySet()) {
            for (String value : entry.getValue()) {
                if (i++ > 0) sb.append('&');
                sb.append(URLEncoder.encode(entry.getKey(), StandardCharsets.UTF_8))
                        .append('=')
                        .append(URLEncoder.encode(value, StandardCharsets.UTF_8));
            }
        }
        return sb.toString();
    }

    @Override
    public GoogleProfile fetchProfile(String code) {
        String accessToken = exchangeCodeForAccessToken(code);
        try {
            GoogleUserInfo info = restClient.get()
                    .uri(USERINFO_URL)
                    .headers(h -> h.setBearerAuth(accessToken))
                    .accept(MediaType.APPLICATION_JSON)
                    .retrieve()
                    .body(GoogleUserInfo.class);
            if (info == null || info.sub() == null || info.email() == null) {
                throw AuthFlowException.googleAuthFailed();
            }
            String name = info.name() != null ? info.name() : info.email();
            return new GoogleProfile(info.sub(), info.email(), name, info.picture(),
                    Boolean.TRUE.equals(info.email_verified()));
        } catch (AuthFlowException e) {
            throw e;
        } catch (RuntimeException e) {
            throw AuthFlowException.googleAuthFailed();
        }
    }

    private String exchangeCodeForAccessToken(String code) {
        try {
            MultiValueMap<String, String> form = new LinkedMultiValueMap<>();
            form.add("client_id", clientId);
            form.add("client_secret", clientSecret);
            form.add("code", code);
            form.add("grant_type", "authorization_code");
            form.add("redirect_uri", redirectUri);
            TokenResponse response = restClient.post()
                    .uri(TOKEN_URL)
                    .contentType(MediaType.APPLICATION_FORM_URLENCODED)
                    .accept(MediaType.APPLICATION_JSON)
                    .body(form)
                    .retrieve()
                    .body(TokenResponse.class);
            if (response == null || response.access_token() == null) {
                throw AuthFlowException.googleAuthFailed();
            }
            return response.access_token();
        } catch (AuthFlowException e) {
            throw e;
        } catch (RuntimeException e) {
            throw AuthFlowException.googleAuthFailed();
        }
    }

    record GoogleUserInfo(String sub, String email, String name, String picture, Boolean email_verified) {
    }

    record TokenResponse(String access_token) {
    }
}