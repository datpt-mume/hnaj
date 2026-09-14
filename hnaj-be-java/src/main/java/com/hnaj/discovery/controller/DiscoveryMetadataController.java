package com.hnaj.discovery.controller;

import com.hnaj.api.common.ApiResponse;
import com.hnaj.discovery.service.DiscoveryMetadataService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

/**
 * GET /api/meta/discovery — public endpoint, no auth required.
 * Returns active categories, districts, and tags for the filter UI.
 * Equivalent to Laravel DiscoveryMetadataController.
 */
@RestController
@RequestMapping("/api")
public class DiscoveryMetadataController {

    private final DiscoveryMetadataService discoveryMetadataService;

    public DiscoveryMetadataController(DiscoveryMetadataService discoveryMetadataService) {
        this.discoveryMetadataService = discoveryMetadataService;
    }

    @GetMapping("/meta/discovery")
    public ResponseEntity<Map<String, Object>> getDiscoveryMetadata() {
        return ApiResponse.success(discoveryMetadataService.get());
    }
}
