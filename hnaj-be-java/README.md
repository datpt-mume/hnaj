# hnaj-be-java

HNAJ Backend — Java Spring Boot (migration từ [`../hnaj-be/`](../hnaj-be/) Laravel).

## Stack (đã duyệt)

| Lớp | Công nghệ |
|---|---|
| Runtime | Java 21 LTS |
| Framework | Spring Boot 3.3.5 |
| Persistence | Spring Data JPA + Hibernate, MySQL 8.4 |
| Schema | Flyway baseline `V1__baseline_schema.sql` (gộp 38 migration Laravel) |
| Build | Maven 3.9+ |
| Cache | Caffeine in-memory (Google OAuth state 60s) |
| Rate limit | Bucket4j |
| Security | Spring Security (BCrypt rounds=12, tương thích Sanctum hash) |

## Quyết định kiến trúc (Q1–Q6, 2026-09-05)

- **Package:** `com.hnaj`
- **DB:** `hnaj_java` (riêng) + `hnaj_java_test` (test profile) — KHÔNG đụng `hnaj`/`hnaj_test` của Laravel
- **Flyway:** baseline V1 (gộp 38 migration vì DB mới)
- **Cache:** Caffeine in-memory
- **Compose:** file riêng `docker-compose.spring.yaml` ở `hnaj-docker/` (Q15)
- **API contract:** Spring phát hành token riêng; chỉ tương thích API envelope, không tương thích Sanctum token format

## Cấu trúc

```
hnaj-be-java/
├── pom.xml
├── Dockerfile
├── README.md
├── src/
│   ├── main/
│   │   ├── java/com/hnaj/
│   │   │   └── HnajBeJavaApplication.java
│   │   └── resources/
│   │       ├── application.yml
│   │       └── db/migration/
│   │           └── V1__baseline_schema.sql
│   └── test/
│       └── java/com/hnaj/api/
│           └── ApiSmokeTest.java
```

## Cách chạy local (chưa dùng Docker)

```bash
# Yêu cầu MySQL sẵn có, tạo DB trước:
#   CREATE DATABASE hnaj_java CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
mvn spring-boot:run
```

## Cách chạy trong Docker Compose

```bash
# Từ thư mục hnaj-docker/
docker compose -f docker-compose.spring.yaml --env-file .env up -d backend-java
docker compose -f docker-compose.spring.yaml --env-file .env exec backend-java \
  curl -s http://127.0.0.1:8083/api/test
```

## Kiểm chứng Phase 1

```bash
mvn -B test
# Kỳ vọng: 1 test pass (ApiSmokeTest) - chỉ kiểm tra context load + endpoint /api/test trả 200.
```

## Đối chiếu triển khai

- Knowledge base: [`../docs/migration/knowledge-base/`](../docs/migration/knowledge-base/)
- Traceability matrix: [`06-traceability-matrix.md`](../docs/migration/knowledge-base/06-traceability-matrix.md)
- Project progress: [`../../plans/project-progress.md`](../../plans/project-progress.md)

## Quy tắc (AGENTS.md)

- Không tự thay version tool, không sửa test để che lỗi, không tự commit.
- Phase 2+ phải điền traceability matrix sau mỗi domain hoàn thành.
- Mọi lệnh build/test chạy trong Docker Compose (trừ khi user cho phép host).
