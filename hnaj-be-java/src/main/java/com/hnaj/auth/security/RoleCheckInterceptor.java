package com.hnaj.auth.security;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.util.Arrays;
import org.springframework.lang.NonNull;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.stereotype.Component;
import org.springframework.web.method.HandlerMethod;
import org.springframework.web.servlet.HandlerInterceptor;

/**
 * Enforces @RequireRole. Authentication happens in BearerAuthFilter; an endpoint that
 * requires a role but has no authenticated principal gets a 401, mismatched role a 403.
 */
@Component
public class RoleCheckInterceptor implements HandlerInterceptor {
    private final SecurityErrorWriter errorWriter;

    public RoleCheckInterceptor(SecurityErrorWriter errorWriter) {
        this.errorWriter = errorWriter;
    }

    @Override
    public boolean preHandle(
            @NonNull HttpServletRequest request,
            @NonNull HttpServletResponse response,
            @NonNull Object handler) throws Exception {
        if (!(handler instanceof HandlerMethod method)) {
            return true;
        }
        RequireRole require = method.getMethodAnnotation(RequireRole.class);
        if (require == null) {
            return true;
        }

        Authentication authentication = SecurityContextHolder.getContext().getAuthentication();
        if (authentication == null || !authentication.isAuthenticated()
                || !(authentication.getPrincipal() instanceof com.hnaj.auth.AuthenticatedUser)) {
            errorWriter.write(response, org.springframework.http.HttpStatus.UNAUTHORIZED,
                    "UNAUTHENTICATED",
                    "Authentication is required to access this resource.");
            return false;
        }

        boolean authorized = Arrays.stream(require.value())
                .anyMatch(required -> authentication.getAuthorities().stream()
                        .anyMatch(auth -> auth.getAuthority()
                                .equals("ROLE_" + required.toUpperCase())));
        if (!authorized) {
            errorWriter.write(response, org.springframework.http.HttpStatus.FORBIDDEN,
                    "FORBIDDEN_ROLE",
                    "You do not have permission to access this resource.");
            return false;
        }
        return true;
    }
}