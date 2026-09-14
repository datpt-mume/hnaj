package com.hnaj.auth;

import com.hnaj.auth.entity.User;

/** The authenticated principal: DB user plus the exact token used for the request. */
public record AuthenticatedUser(User user, String rawToken) {
}
