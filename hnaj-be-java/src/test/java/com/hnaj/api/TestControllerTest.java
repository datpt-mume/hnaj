package com.hnaj.api;

import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.web.servlet.MockMvc;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/**
 * Smoke test endpoint #1 (GET /api/test) — verify envelope shape khớp
 * Laravel TestController (docs/migration/knowledge-base/00-inventory-endpoints.md #1).
 */
@SpringBootTest
@AutoConfigureMockMvc
@org.springframework.test.context.ActiveProfiles("smoke")
class TestControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Test
    void returnsSuccessEnvelopeWithServiceStatus() throws Exception {
        mockMvc.perform(get("/api/test"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.success").value(true))
                .andExpect(jsonPath("$.message").value("API connection is working."))
                .andExpect(jsonPath("$.data.service").value("hnaj-be-java"))
                .andExpect(jsonPath("$.data.status").value("ok"));
    }
}
