package com.hnaj.auth;

/** Login/exchange success payload, mirrors Laravel {'user' => UserResource, 'token' => string}. */
public record AuthResult(UserView user, String token) {
}
