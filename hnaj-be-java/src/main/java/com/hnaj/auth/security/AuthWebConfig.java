package com.hnaj.auth.security;

import org.springframework.context.annotation.Configuration;
import org.springframework.web.method.support.HandlerMethodArgumentResolver;
import org.springframework.web.servlet.config.annotation.InterceptorRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;
import java.util.List;

@Configuration
public class AuthWebConfig implements WebMvcConfigurer {
    private final RoleCheckInterceptor roleCheckInterceptor;
    private final AuthenticatedUserArgumentResolver authenticatedUserArgumentResolver;

    public AuthWebConfig(
            RoleCheckInterceptor roleCheckInterceptor,
            AuthenticatedUserArgumentResolver authenticatedUserArgumentResolver) {
        this.roleCheckInterceptor = roleCheckInterceptor;
        this.authenticatedUserArgumentResolver = authenticatedUserArgumentResolver;
    }

    @Override
    public void addInterceptors(InterceptorRegistry registry) {
        registry.addInterceptor(roleCheckInterceptor).addPathPatterns("/api/**");
    }

    @Override
    public void addArgumentResolvers(List<HandlerMethodArgumentResolver> resolvers) {
        resolvers.add(authenticatedUserArgumentResolver);
    }
}
