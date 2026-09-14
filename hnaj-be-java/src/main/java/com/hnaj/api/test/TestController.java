package com.hnaj.api.test;

import com.hnaj.api.common.ApiResponse;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

/**
 * Endpoint #1: GET /api/test — health endpoint tương thích Laravel TestController.
 * Mapping: endpoint đầu tiên để verify FE ↔ Spring connection + envelope shape.
 */
@RestController
@RequestMapping("/api")
public class TestController {

    @GetMapping("/test")
    public ResponseEntity<Map<String, Object>> test() {
        return ApiResponse.success(
                Map.of(
                        "service", "hnaj-be-java",
                        "status", "ok"
                ),
                "API connection is working.",
                null
        );
    }
}
