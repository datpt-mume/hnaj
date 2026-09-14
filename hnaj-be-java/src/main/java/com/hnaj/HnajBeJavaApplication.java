package com.hnaj;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.cache.annotation.EnableCaching;

/**
 * HNAJ Backend — Java Spring Boot (migration từ hnaj-be Laravel).
 *
 * Phase 1: scaffold. Phase 2+: triển khai từng domain theo
 * docs/migration/knowledge-base/06-traceability-matrix.md.
 *
 * Quy tắc kiến trúc (AGENTS.md §7):
 *   Route (controller) -> Service/Action -> Repository -> Model
 * Validation ở biên; authorization đọc role từ DB tại mỗi request.
 */
@SpringBootApplication
@EnableCaching
public class HnajBeJavaApplication {

    public static void main(String[] args) {
        SpringApplication.run(HnajBeJavaApplication.class, args);
    }
}
