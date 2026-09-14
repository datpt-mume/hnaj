package com.hnaj.api;

import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.http.MediaType;
import org.springframework.test.context.ActiveProfiles;
import org.springframework.test.web.servlet.MockMvc;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/**
 * Endpoint-level contract checks that need no database (smoke/H2):
 * security envelope (401), rate limit (429), validation (422) and
 * Google-not-configured behavior. DB-backed flows are covered by MySQL integration.
 */
@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("smoke")
class AuthSecurityContractTest {

    @Autowired
    private MockMvc mockMvc;

    @Test
    void meRequiresAuthenticationAndReturnsUnauthenticatedEnvelope() throws Exception {
        mockMvc.perform(get("/api/auth/me"))
                .andExpect(status().isUnauthorized())
                .andExpect(jsonPath("$.success").value(false))
                .andExpect(jsonPath("$.code").value("UNAUTHENTICATED"))
                .andExpect(jsonPath("$.message").value("Authentication is required to access this resource."));
    }

    @Test
    void adminMeRequiresAuthentication() throws Exception {
        mockMvc.perform(get("/api/admin/auth/me"))
                .andExpect(status().isUnauthorized())
                .andExpect(jsonPath("$.code").value("UNAUTHENTICATED"));
    }

    @Test
    void loginIsRateLimitedAtTheBoundary() throws Exception {
        String body = "{\"username\":\"alice\",\"password\":\"whatever1\"}";
        // The first five requests pass the filter (their status depends on DB-backed
        // credential checks covered by MySQL integration; here the profile has no tables).
        for (int i = 0; i < 5; i++) {
            mockMvc.perform(post("/api/auth/login")
                    .contentType(MediaType.APPLICATION_JSON).content(body));
        }
        // The sixth request must be rejected by the rate limit BEFORE reaching the DB.
        mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON).content(body))
                .andExpect(status().isTooManyRequests())
                .andExpect(jsonPath("$.success").value(false))
                .andExpect(jsonPath("$.code").value("TOO_MANY_REQUESTS"))
                .andExpect(jsonPath("$.message").value("Too Many Attempts."));
    }

    @Test
    void registerRejectsInvalidUsernameWithValidationEnvelope() throws Exception {
        mockMvc.perform(post("/api/auth/register")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"_bad\",\"full_name\":\"A B\",\"email\":\"a@b.com\","
                                + "\"password\":\"secret1\",\"password_confirmation\":\"secret1\"}"))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.success").value(false))
                .andExpect(jsonPath("$.code").value("VALIDATION_ERROR"))
                .andExpect(jsonPath("$.errors.username").isArray());
    }

    @Test
    void googleRedirectFailsCleanlyWhenNotConfigured() throws Exception {
        mockMvc.perform(get("/api/auth/google/redirect"))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.success").value(false))
                .andExpect(jsonPath("$.code").value("GOOGLE_AUTH_FAILED"));
    }

    @Test
    void setUpdatesMeWithMalformedBodyValidation() throws Exception {
        mockMvc.perform(post("/api/auth/account/setup")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"token\":\"x\",\"password\":\"123\"}"))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.code").value("VALIDATION_ERROR"));
    }
}