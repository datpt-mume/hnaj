package com.hnaj.auth.security;

import com.hnaj.auth.AccessTokenService;
import com.hnaj.auth.AuthRoleService;
import com.hnaj.auth.AuthenticatedUser;
import com.hnaj.auth.entity.AccessToken;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.util.List;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.core.authority.SimpleGrantedAuthority;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;

/**
 * Stateless bearer filter. Roles are read from the database on every request so an
 * admin can revoke access immediately (knowledge-base 04-auth-security §Authorization).
 */
@Component
public class BearerAuthFilter extends OncePerRequestFilter {
    private final AccessTokenService accessTokens;
    private final AuthRoleService roles;

    public BearerAuthFilter(AccessTokenService accessTokens, AuthRoleService roles) {
        this.accessTokens = accessTokens;
        this.roles = roles;
    }

    @Override
    protected void doFilterInternal(
            HttpServletRequest request,
            HttpServletResponse response,
            FilterChain chain) throws ServletException, IOException {
        String header = request.getHeader("Authorization");
        if (header != null && header.regionMatches(true, 0, "Bearer ", 0, 7)) {
            String rawToken = header.substring(7).trim();
            accessTokens.resolve(rawToken).ifPresent(token -> authenticate(token, rawToken));
        }
        chain.doFilter(request, response);
    }

    private void authenticate(AccessToken token, String rawToken) {
        List<String> roleNames = roles.namesOf(token.getUser().getId());
        var authorities = roleNames.stream()
                .map(name -> new SimpleGrantedAuthority("ROLE_" + name.toUpperCase()))
                .toList();
        var principal = new AuthenticatedUser(token.getUser(), rawToken);
        var authentication = new UsernamePasswordAuthenticationToken(principal, rawToken, authorities);
        SecurityContextHolder.getContext().setAuthentication(authentication);
    }
}
