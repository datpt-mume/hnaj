package com.hnaj.api.common;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;

import java.util.LinkedHashMap;
import java.util.Map;

/**
 * Helper dựng response envelope chung cho mọi endpoint.
 * Tương ứng 1-1 với {@code App\Http\Responses\ApiResponse} trong Laravel.
 *
 * Quy tắc:
 *  - success=true khi HTTP 2xx
 *  - meta chỉ thêm khi không rỗng
 *  - errors + code chỉ có ở error envelope
 *
 * Xem chi tiết: docs/migration/knowledge-base/01-api-contract.md
 */
public final class ApiResponse {

    private ApiResponse() {
        // utility class
    }

    public static ResponseEntity<Map<String, Object>> success(
            Object data,
            String message,
            Map<String, Object> meta
    ) {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("success", true);
        body.put("message", message != null ? message : "Request completed successfully.");
        body.put("data", data);
        if (meta != null && !meta.isEmpty()) {
            body.put("meta", meta);
        }
        return ResponseEntity.status(HttpStatus.OK).body(body);
    }

    public static ResponseEntity<Map<String, Object>> success(Object data) {
        return success(data, "Request completed successfully.", null);
    }

    /** Success with explicit HTTP status (e.g. 201 Created for register). */
    public static ResponseEntity<Map<String, Object>> success(
            Object data,
            String message,
            Map<String, Object> meta,
            HttpStatus status
    ) {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("success", true);
        body.put("message", message != null ? message : "Request completed successfully.");
        body.put("data", data);
        if (meta != null && !meta.isEmpty()) {
            body.put("meta", meta);
        }
        return ResponseEntity.status(status).body(body);
    }

    public static ResponseEntity<Map<String, Object>> error(
            String message,
            Map<String, Object> errors,
            String code,
            HttpStatus status
    ) {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("success", false);
        body.put("message", message != null ? message : "The request could not be completed.");
        if (errors != null && !errors.isEmpty()) {
            body.put("errors", errors);
        }
        if (code != null && !code.isEmpty()) {
            body.put("code", code);
        }
        return ResponseEntity.status(status).body(body);
    }
}
