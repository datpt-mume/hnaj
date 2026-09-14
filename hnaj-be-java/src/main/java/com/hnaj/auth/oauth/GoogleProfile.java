package com.hnaj.auth.oauth;

/** Profile returned after exchanging an OAuth code, mirroring Laravel GoogleOAuthClient. */
public record GoogleProfile(
        String googleId,
        String email,
        String name,
        String avatarUrl,
        boolean emailVerified) {
}