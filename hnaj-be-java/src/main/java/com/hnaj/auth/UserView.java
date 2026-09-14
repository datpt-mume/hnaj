package com.hnaj.auth;

import com.fasterxml.jackson.annotation.JsonProperty;
import com.hnaj.auth.entity.User;
import java.util.List;

/**
 * Public user payload, mirrors Laravel {@code UserResource}
 * (snake_case fields — this is the wire contract the frontend consumes).
 * Never exposes internal fields (password, google_id, remember_token).
 */
public record UserView(
        Long id,
        String username,
        @JsonProperty("full_name") String fullName,
        String email,
        @JsonProperty("avatar_url") String avatarUrl,
        String status,
        @JsonProperty("email_verified") boolean emailVerified,
        List<String> roles) {

    public static UserView from(User user, List<String> roleNames) {
        return new UserView(
                user.getId(),
                user.getUsername(),
                user.getName(),
                user.getEmail(),
                user.getAvatarUrl(),
                user.getStatus(),
                user.getEmailVerifiedAt() != null,
                roleNames == null ? List.of() : List.copyOf(roleNames));
    }
}