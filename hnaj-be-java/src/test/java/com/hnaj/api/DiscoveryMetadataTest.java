package com.hnaj.api;

import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.test.context.ActiveProfiles;
import org.springframework.test.web.servlet.MockMvc;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/**
 * Port of Laravel DiscoveryMetadataTest — verify GET /api/meta/discovery
 * returns only active taxonomy items sorted by name (KB 00 #2, 03 §3).
 *
 * Seam: HTTP boundary (MockMvc + MySQL), same seam as AuthFlowIntegrationTest.
 * No JPA entities required for inserts — raw JDBC via JdbcTemplate.
 */
@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class DiscoveryMetadataTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private JdbcTemplate jdbc;

    @BeforeEach
    void setUp() {
        // Clean taxonomy tables — no transaction rollback on MySQL real DB
        jdbc.execute("DELETE FROM tags");
        jdbc.execute("DELETE FROM categories");
        jdbc.execute("DELETE FROM districts");
    }

    @Test
    void returnsActiveDiscoveryMetadataOnly() throws Exception {
        long now = System.currentTimeMillis() / 1000;

        // Active category
        jdbc.update(
                "INSERT INTO categories (name, slug, status, created_at, updated_at) VALUES (?, ?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Ăn uống", "an-uong", now, now);
        long activeCategoryId = jdbc.queryForObject("SELECT id FROM categories WHERE slug = 'an-uong'", Long.class);

        // Inactive category
        jdbc.update(
                "INSERT INTO categories (name, slug, status, created_at, updated_at) VALUES (?, ?, 'inactive', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Inactive Cat", "inactive-cat", now, now);

        // Active district
        jdbc.update(
                "INSERT INTO districts (name, status, created_at, updated_at) VALUES (?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Ba Đình", now, now);
        long activeDistrictId = jdbc.queryForObject("SELECT id FROM districts WHERE name = 'Ba Đình'", Long.class);

        // Inactive district
        jdbc.update(
                "INSERT INTO districts (name, status, created_at, updated_at) VALUES (?, 'inactive', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Inactive District", now, now);

        // Active tag
        jdbc.update(
                "INSERT INTO tags (name, slug, status, created_at, updated_at) VALUES (?, ?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Chill", "chill", now, now);
        long activeTagId = jdbc.queryForObject("SELECT id FROM tags WHERE slug = 'chill'", Long.class);

        // Active tag that will be soft-deleted
        jdbc.update(
                "INSERT INTO tags (name, slug, status, created_at, updated_at, deleted_at) VALUES (?, ?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Deleted Tag", "deleted-tag", now, now, now);

        mockMvc.perform(get("/api/meta/discovery"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.success").value(true))
                .andExpect(jsonPath("$.data.categories.length()").value(1))
                .andExpect(jsonPath("$.data.categories[0].id").value(activeCategoryId))
                .andExpect(jsonPath("$.data.categories[0].name").value("Ăn uống"))
                .andExpect(jsonPath("$.data.categories[0].slug").value("an-uong"))
                .andExpect(jsonPath("$.data.districts.length()").value(1))
                .andExpect(jsonPath("$.data.districts[0].id").value(activeDistrictId))
                .andExpect(jsonPath("$.data.districts[0].name").value("Ba Đình"))
                .andExpect(jsonPath("$.data.districts[0].code").doesNotExist())
                .andExpect(jsonPath("$.data.tags.length()").value(1))
                .andExpect(jsonPath("$.data.tags[0].id").value(activeTagId))
                .andExpect(jsonPath("$.data.tags[0].name").value("Chill"));
    }

    @Test
    void metadataIsSortedByName() throws Exception {
        long now = System.currentTimeMillis() / 1000;

        jdbc.update(
                "INSERT INTO categories (name, slug, status, created_at, updated_at) VALUES (?, ?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Zeta", "zeta", now, now);
        jdbc.update(
                "INSERT INTO categories (name, slug, status, created_at, updated_at) VALUES (?, ?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Alpha", "alpha", now, now);

        jdbc.update(
                "INSERT INTO districts (name, status, created_at, updated_at) VALUES (?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Zeta", now, now);
        jdbc.update(
                "INSERT INTO districts (name, status, created_at, updated_at) VALUES (?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Alpha", now, now);

        jdbc.update(
                "INSERT INTO tags (name, slug, status, created_at, updated_at) VALUES (?, ?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Zeta", "zeta", now, now);
        jdbc.update(
                "INSERT INTO tags (name, slug, status, created_at, updated_at) VALUES (?, ?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                "Alpha", "alpha", now, now);

        mockMvc.perform(get("/api/meta/discovery"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.categories[0].name").value("Alpha"))
                .andExpect(jsonPath("$.data.categories[1].name").value("Zeta"))
                .andExpect(jsonPath("$.data.districts[0].name").value("Alpha"))
                .andExpect(jsonPath("$.data.districts[1].name").value("Zeta"))
                .andExpect(jsonPath("$.data.tags[0].name").value("Alpha"))
                .andExpect(jsonPath("$.data.tags[1].name").value("Zeta"));
    }
}
