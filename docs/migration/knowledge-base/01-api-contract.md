# 01 — API Contract (Response Envelope & Error Codes)

> Nguồn sự thật:
> - [`hnaj-be/app/Http/Responses/ApiResponse.php`](../../../hnaj-be/app/Http/Responses/ApiResponse.php:7)
> - [`hnaj-be/app/Exceptions/ApiExceptionHandler.php`](../../../hnaj-be/app/Exceptions/ApiExceptionHandler.php:17)
> - [`hnaj-be/app/Enums/AuthErrorCode.php`](../../../hnaj-be/app/Enums/AuthErrorCode.php:1)
> - [`hnaj-be/app/Enums/BookmarkErrorCode.php`](../../../hnaj-be/app/Enums/BookmarkErrorCode.php:1)
> - [`hnaj-be/app/Enums/VisitErrorCode.php`](../../../hnaj-be/app/Enums/VisitErrorCode.php:1)

---

## 1. Success Envelope

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": <any>,
  "meta": {}   // tùy chọn, chỉ có khi không rỗng
}
```

### Spring Boot mapping

```java
// ApiResponse.success(data, message, meta, status)
ResponseEntity<Map<String, Object>> body = Map.of(
    "success", true,
    "message", message,
    "data", data
    // "meta" chỉ thêm khi meta != null && !meta.isEmpty()
);
```

**Quy tắc bắt buộc:**
- `success: true` khi HTTP 2xx.
- `meta` chỉ xuất hiện trong response khi có giá trị (không trả `"meta": null` hay `"meta": {}`).
- `data` có thể là `null`, object, array.

---

## 2. Error Envelope

```json
{
  "success": false,
  "message": "Human-readable error message.",
  "errors": { "field": ["validation message"] },  // tùy chọn
  "code": "ERROR_CODE_STRING"                       // tùy chọn
}
```

**Quy tắc bắt buộc:**
- `success: false` khi HTTP 4xx/5xx.
- `errors` chỉ có khi validation error (422).
- `code` là string enum ổn định, FE dựa vào để xử lý logic (không parse `message`).
- Không lộ stack trace, SQL query, class name hay chi tiết nội bộ trong `message`.

---

## 3. Exception → Error Envelope Mapping

| Exception class | HTTP status | `code` | Ghi chú |
|----------------|-------------|--------|---------|
| `AuthFlowException` | Vary (xem bên dưới) | `errorCode.value` | Mỗi factory method có status riêng |
| `BookmarkException` | Vary | `errorCode.value` | |
| `VisitException` | Vary | `errorCode.value` | |
| `AuthenticationException` (Sanctum) | 401 | `UNAUTHENTICATED` | Token thiếu hoặc invalid |
| `ValidationException` | 422 | `VALIDATION_ERROR` | `errors` = field-level messages |
| `NotFoundHttpException` | 404 | `NOT_FOUND` | Route/model không tìm thấy |
| `HttpExceptionInterface` | status code từ exception | `HTTP_ERROR` | |
| mọi `Throwable` khác | 500 | `INTERNAL_SERVER_ERROR` | Message: "An unexpected error occurred." |

### Spring Boot implementation

Implement `@RestControllerAdvice` xử lý từng exception type theo bảng trên. Không để Spring Boot trả default error page hay Whitelabel Error Page cho `/api/*`.

---

## 4. AuthErrorCode Enum

```java
public enum AuthErrorCode {
    INVALID_CREDENTIALS,        // sai username/password → 401
    EMAIL_NOT_VERIFIED,         // chưa verify email → 403
    ACCOUNT_NOT_ACTIVE,         // account bị suspend → 403
    INVALID_VERIFICATION_TOKEN, // token verify sai/hết hạn/đã dùng → 422
    EMAIL_ALREADY_VERIFIED,     // email đã verify → 409
    UNAUTHENTICATED,            // thiếu/sai bearer token → 401
    FORBIDDEN_ROLE,             // thiếu role → 403
    GOOGLE_AUTH_FAILED          // OAuth Google thất bại → 422
}
```

### AuthFlowException factory → HTTP status

| Factory method | HTTP Status | Code |
|---|---|---|
| `invalidCredentials()` | 401 | `INVALID_CREDENTIALS` |
| `emailNotVerified()` | 403 | `EMAIL_NOT_VERIFIED` |
| `accountNotActive()` | 403 | `ACCOUNT_NOT_ACTIVE` |
| `invalidVerificationToken()` | 422 | `INVALID_VERIFICATION_TOKEN` |
| `emailAlreadyVerified()` | 409 | `EMAIL_ALREADY_VERIFIED` |
| `forbiddenRole()` | 403 | `FORBIDDEN_ROLE` |
| `googleAuthFailed(msg)` | 422 | `GOOGLE_AUTH_FAILED` |

---

## 5. BookmarkErrorCode Enum

*(Đọc từ [`hnaj-be/app/Enums/BookmarkErrorCode.php`](../../../hnaj-be/app/Enums/BookmarkErrorCode.php:1) khi implement domain Bookmark)*

---

## 6. VisitErrorCode Enum

*(Đọc từ [`hnaj-be/app/Enums/VisitErrorCode.php`](../../../hnaj-be/app/Enums/VisitErrorCode.php:1) khi implement domain Visit)*

Các case đã xác nhận từ [`RecordVisit`](../../../hnaj-be/app/Actions/Visit/RecordVisit.php:34):
- `PlaceNotAvailable` → 404 — địa điểm không tồn tại hoặc không khả dụng
- `AnonymousKeyRequired` → 422 — thiếu `X-Anonymous-Id` khi guest

---

## 7. Validation Error Format

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required.", "The email must be a valid email address."],
    "password": ["The password must be at least 8 characters."]
  },
  "code": "VALIDATION_ERROR"
}
```

**Spring Boot:** Dùng `MethodArgumentNotValidException` + `BindException`, extract `BindingResult` để build `errors` map theo đúng cấu trúc trên.

---

## 8. Role-based Auth

### EnsureUserHasRole behavior

Nguồn: [`EnsureUserHasRole`](../../../hnaj-be/app/Http/Middleware/EnsureUserHasRole.php:18)

- **Không có user (token thiếu/invalid):** 401 `UNAUTHENTICATED`
- **Có user nhưng sai role:** 403 `FORBIDDEN_ROLE`
- **Role đọc từ DB tại mỗi request** — không lưu trong JWT/token payload → Admin thu hồi role có hiệu lực ngay.

### Role names (từ DB bảng `roles`)

| Role | String value | Quyền |
|------|-------------|-------|
| `User` | `user` | bookmark, visit, review, submit manager application |
| `SubAdmin` | `sub_admin` | quản lý place được gán; dùng chung `/auth/login`, `/auth/me` với User |
| `Admin` | `admin` | toàn quyền hệ thống; login qua `/admin/auth/login` riêng |

### Spring Boot approach

Không dùng Spring Security roles từ JWT. Tại mỗi request với `@PreAuthorize` / custom filter:
1. Load user từ token (xem bảng `personal_access_tokens`).
2. Query `user_roles` JOIN `roles` để lấy role names.
3. So sánh với required roles.

---

## 9. Throttle / Rate Limiting

Laravel dùng `throttle:60,1` (60 req/1 min). Spring Boot cần implement tương đương.
Giá trị cụ thể từng endpoint xem [`00-inventory-endpoints.md`](./00-inventory-endpoints.md).

**Không public quota** trong response — backend control only.

---

## 10. Pagination Meta Format

Laravel `LengthAwarePaginator` → Spring Boot `Page<T>`:

```json
{
  "success": true,
  "message": "...",
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 100,
    "last_page": 10
  }
}
```

**Spring Boot:** Map `Page<T>` thành meta object trên. Không trả `links` hay format Pageable mặc định của Spring.

---

## 11. Bearer Token Format

**Laravel (Sanctum):** client gửi `Authorization: Bearer {plaintext_token}`; token được lưu hashed SHA-256 trong cột `token` của bảng `personal_access_tokens`; format `{tokenable_type}|{tokenable_id}|{plaintext}` — Sanctum tự parse.

**Spring (Q17, chốt 2026-09-05):** Spring phát hành **opaque token riêng** (64 hex ngẫu nhiên), DB lưu SHA-256 hash trong bảng `access_tokens` (Flyway `V2__access_tokens.sql`: `token_hash` UNIQUE, `expires_at` NULL indexed) — **KHÔNG tương thích format Sanctum** và không chạy chung DB với Laravel (DB riêng `hnaj_java`, Q2). Contract wire bất biến với FE: `Authorization: Bearer {plaintext}`; roles **không** nằm trong token (đọc DB mỗi request).

**Spring Boot implementation:** `BearerAuthFilter` (OncePerRequestFilter) → hash SHA-256 → query `access_tokens` → load user + roles mới từ DB → set `SecurityContext` (authorities `ROLE_<UPPER>`).
