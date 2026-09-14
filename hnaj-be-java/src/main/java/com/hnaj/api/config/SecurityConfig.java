package com.hnaj.api.config;

import com.hnaj.auth.security.BearerAuthFilter;
import com.hnaj.auth.security.SecurityErrorWriter;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.http.HttpMethod;
import org.springframework.http.HttpStatus;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.config.http.SessionCreationPolicy;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.web.SecurityFilterChain;
import org.springframework.security.web.authentication.UsernamePasswordAuthenticationFilter;
import org.springframework.web.cors.CorsConfiguration;
import org.springframework.web.cors.CorsConfigurationSource;
import org.springframework.web.cors.UrlBasedCorsConfigurationSource;
import java.util.List;

/**
 * Stateless bearer security. Bearer filter runs on public + protected endpoints; role
 * enforcement (reading roles from DB) happens in RoleCheckInterceptor via @RequireRole.
 * Authentication/access exceptions write the shared error envelope.
 */
@Configuration
@EnableWebSecurity
public class SecurityConfig {

    private final BearerAuthFilter bearerAuthFilter;
    private final SecurityErrorWriter errorWriter;
    private final com.hnaj.auth.ratelimit.RateLimitFilter rateLimitFilter;
    private final String allowedOrigin;

    public SecurityConfig(
            BearerAuthFilter bearerAuthFilter,
            SecurityErrorWriter errorWriter,
            com.hnaj.auth.ratelimit.RateLimitFilter rateLimitFilter,
            org.springframework.core.env.Environment env) {
        this.bearerAuthFilter = bearerAuthFilter;
        this.errorWriter = errorWriter;
        this.rateLimitFilter = rateLimitFilter;
        this.allowedOrigin = env.getProperty("hnaj.cors.allowed-origins", "http://localhost:8082");
    }

    @Bean
    public SecurityFilterChain securityFilterChain(HttpSecurity http) throws Exception {
        http
                .csrf(csrf -> csrf.disable())
                .cors(cors -> cors.configurationSource(corsConfigurationSource()))
                .sessionManagement(session -> session.sessionCreationPolicy(SessionCreationPolicy.STATELESS))
                .authorizeHttpRequests(auth -> auth
                        .requestMatchers(HttpMethod.OPTIONS, "/**").permitAll()
                        .requestMatchers("/api/test").permitAll()
                        // Public auth endpoints (mirror hnaj-be/routes/api.php)
                        .requestMatchers(HttpMethod.POST, "/api/auth/register",
                                "/api/auth/login", "/api/auth/email/verify",
                                "/api/auth/email/resend", "/api/auth/account/setup",
                                "/api/auth/google/exchange").permitAll()
                        .requestMatchers(HttpMethod.GET, "/api/auth/google/redirect",
                                "/api/auth/google/callback").permitAll()
                        .requestMatchers(HttpMethod.POST, "/api/admin/auth/login").permitAll()
                        // Bearer-required auth endpoints (roles enforced via @RequireRole)
                        .requestMatchers("/api/auth/me", "/api/auth/logout",
                                "/api/admin/auth/me", "/api/admin/auth/logout").authenticated()
                        .anyRequest().permitAll())
                .exceptionHandling(ex -> ex
                        .authenticationEntryPoint((request, response, exception) ->
                                errorWriter.write(response, HttpStatus.UNAUTHORIZED, "UNAUTHENTICATED",
                                        "Authentication is required to access this resource."))
                        .accessDeniedHandler((request, response, exception) ->
                                errorWriter.write(response, HttpStatus.FORBIDDEN, "FORBIDDEN_ROLE",
                                        "You do not have permission to access this resource.")))
                .addFilterBefore(bearerAuthFilter, UsernamePasswordAuthenticationFilter.class)
                .addFilterBefore(rateLimitFilter, com.hnaj.auth.security.BearerAuthFilter.class);
        return http.build();
    }

    @Bean
    public PasswordEncoder passwordEncoder() {
        // cost 12 to stay compatible with Laravel hashes (knowledge-base 04-auth-security §Password).
        return new BCryptPasswordEncoder(12);
    }

    @Bean
    public CorsConfigurationSource corsConfigurationSource() {
        CorsConfiguration config = new CorsConfiguration();
        config.setAllowedOrigins(List.of(allowedOrigin));
        config.setAllowedMethods(List.of("GET", "POST", "PATCH", "DELETE", "OPTIONS"));
        config.setAllowedHeaders(List.of("Authorization", "Content-Type", "X-Anonymous-Id"));
        config.setAllowCredentials(true);
        config.setMaxAge(3600L);
        UrlBasedCorsConfigurationSource source = new UrlBasedCorsConfigurationSource();
        source.registerCorsConfiguration("/**", config);
        return source;
    }
}