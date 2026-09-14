package com.hnaj.auth.oauth;

/** Abstraction over the Google OAuth flow so tests can stub the provider. */
public interface GoogleOAuthClient {
    boolean isConfigured();

    String buildAuthorizationUrl(String state);

    GoogleProfile fetchProfile(String code);
}