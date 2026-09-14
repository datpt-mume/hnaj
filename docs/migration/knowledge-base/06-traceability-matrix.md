# 06 — Traceability Matrix (công cụ chống miss cases)

> Quy tắc: một hàng chỉ được chuyển `✅ done` khi CẢ 4 cột Laravel/Knowledge/Spring/Test đều xanh.
> Cuối mỗi Phase dùng `.codegraph_explore` đối chiếu: mọi symbol Laravel trong cột "Nguồn Laravel" phải xuất hiện ở cột "Knowledge" hoặc "Spring class".
> ID khớp [`00-inventory-endpoints.md`](./00-inventory-endpoints.md).

## A. Endpoints (39)

| ID | Endpoint | Nguồn Laravel (Controller→Action) | Knowledge doc | Spring class (điền khi code) | Test Laravel nguồn | Test Spring | Parity | Trạng thái |
|----|----------|-----------------------------------|---------------|------------------------------|--------------------|-------------|--------|------------|
| 1 | GET /api/test | TestController | 01 | | ExampleTest | | | ⬜ |
| 2 | GET /api/meta/discovery | DiscoveryMetadataController→GetDiscoveryMetadata | 03§3 | | DiscoveryMetadataTest | | | ⬜ |
| 3 | POST /api/discovery/random | PlaceDiscoveryController→SelectBestPlace+PlaceScorer | 03§3 | | DiscoveryRandomTest, PlaceScorerTest | | | ⬜ |
| 4 | GET /api/places/search | SearchPlaceController→SearchPlaces | 03§4 | | PlaceSearchTest | | | ⬜ |
| 5 | GET /api/places/{place} | PlaceShowController→ShowPlace | 03§4 | | PlaceDetailTest | | | ⬜ |
| 6 | POST /api/auth/register | RegisterController→RegisterUser | 03§1 | RegisterController→UserRegistrationService | RegisterTest | AuthFlowIntegrationTest#register* | replay OK | ✅ |
| 7 | POST /api/auth/login | LoginController→LoginUser+AuthenticateCredentials | 03§1,04 | LoginController→LoginUserService+AuthenticateCredentialsService | LoginTest | AuthFlowIntegrationTest#login* | replay OK | ✅ |
| 8 | POST /api/auth/email/verify | EmailVerificationController→VerifyEmail | 03§1 | EmailVerificationController→VerifyEmailService | EmailVerificationTest | AuthFlowIntegrationTest#verify* | replay OK | ✅ |
| 9 | POST /api/auth/email/resend | EmailVerificationController→ResendEmailVerification | 03§1 | EmailVerificationController→ResendEmailVerificationService | EmailVerificationTest | AuthFlowIntegrationTest#resend* | replay OK | ✅ |
| 10 | POST /api/auth/account/setup | AccountSetupController→CompleteAccountSetup | 03§1 | AccountSetupController→AccountSetupService | AccountSetupTest | AuthFlowIntegrationTest#accountSetup* | replay OK | ✅ |
| 11 | GET /api/auth/google/redirect | GoogleAuthController→RedirectToGoogle | 03§2 | GoogleAuthController→GoogleAuthFlowService.startFlow | GoogleAuthTest | AuthFlowIntegrationTest#google* | replay OK | ✅ |
| 12 | GET /api/auth/google/callback | GoogleAuthController→HandleGoogleCallback | 03§2 | GoogleAuthController→GoogleAuthFlowService.handleCallback | GoogleAuthTest | AuthFlowIntegrationTest#google* | replay OK | ✅ |
| 13 | POST /api/auth/google/exchange | GoogleAuthController→ExchangeGoogleCode | 03§2 | GoogleAuthController→GoogleAuthFlowService.exchange | GoogleAuthTest | AuthFlowIntegrationTest#google* | replay OK | ✅ |
| 14 | GET /api/auth/me | MeController→LoadAuthenticatedUser | 04 | MeController→AuthRoleService | UpdateProfileTest, RoleMiddlewareTest | AuthFlowIntegrationTest#me* | replay OK | ✅ |
| 15 | PATCH /api/auth/me | UpdateProfileController→UpdateProfile | 03§1 | UpdateProfileController→UpdateProfileService | UpdateProfileTest | AuthFlowIntegrationTest#meAndUpdateProfileWithBearer | replay OK | ✅ |
| 16 | POST /api/auth/logout | LogoutController→RevokeAccessTokens | 04 | LogoutController→AccessTokenService.revoke | LoginTest | AuthFlowIntegrationTest#logoutRevokesOnlyTheCurrentToken | replay OK | ✅ |
| 17 | POST /api/manager-applications | SubmitManagerApplicationController→SubmitManagerApplication | 03§7 | | AdminManagerApplicationTest | | | ⬜ |
| 18 | GET /api/bookmarks | BookmarkIndexController→ListBookmarks | 03§5 | | BookmarkTest | | | ⬜ |
| 19 | POST /api/bookmarks | BookmarkStoreController→CreateBookmark | 03§5 | | BookmarkTest | | | ⬜ |
| 20 | DELETE /api/bookmarks/{place} | BookmarkDestroyController→DeleteBookmark | 03§5 | | BookmarkTest | | | ⬜ |
| 21 | POST /api/visits | VisitStoreController→RecordVisit | 03§6 | | VisitTest | | | ⬜ |
| 22 | GET /api/visits | VisitIndexController→ListVisitHistory | 03§6 | | VisitTest | | | ⬜ |
| 23 | POST /api/admin/auth/login | AdminLoginController→LoginAdmin | 03§1 | AdminAuthController→LoginAdminService | AdminLoginTest | AuthFlowIntegrationTest#adminLoginMeAndLogout, bootstrapAdmin* | replay OK | ✅ |
| 24 | GET /api/admin/auth/me | AdminMeController | 04 | AdminAuthController→AuthRoleService | AdminLoginTest | AuthFlowIntegrationTest#adminLoginMeAndLogout | replay OK | ✅ |
| 25 | POST /api/admin/auth/logout | LogoutController | 04 | AdminAuthController→AccessTokenService.revoke | AdminLoginTest | AuthFlowIntegrationTest#adminLoginMeAndLogout | replay OK | ✅ |
| 26 | POST /api/admin/tags | AdminTagStoreController→CreateTag | 03§8 | | AdminTagCreateTest | | | ⬜ |
| 27 | GET /api/admin/manager-applications | AdminManagerApplicationIndexController | 03§7 | | AdminManagerApplicationTest | | | ⬜ |
| 28 | POST /api/admin/manager-applications/{id}/approve | ReviewController@approve→ReviewManagerApplication | 03§7 | | AdminManagerApplicationTest | | | ⬜ |
| 29 | POST /api/admin/manager-applications/{id}/reject | ReviewController@reject→ReviewManagerApplication | 03§7 | | AdminManagerApplicationTest | | | ⬜ |
| 30 | GET /api/admin/places | AdminPlaceIndexController→ListAdminPlaces | 03§8 | | AdminPlaceCrudTest | | | ⬜ |
| 31 | POST /api/admin/places | AdminPlaceStoreController→CreateAdminPlace | 03§8 | | AdminPlaceCrudTest | | | ⬜ |
| 32 | GET /api/admin/places/verification-queue | AdminPlaceVerificationQueueController→GetVerificationQueue | 03§8 | | AdminPlaceCrudTest | | | ⬜ |
| 33 | GET /api/admin/places/{place} | AdminPlaceShowController | 03§8 | | AdminPlaceCrudTest | | | ⬜ |
| 34 | PATCH /api/admin/places/{place} | AdminPlaceUpdateController→UpdateVerifiedPlace | 03§8 | | AdminPlaceOpeningHoursUpdateTest | | | ⬜ |
| 35 | DELETE /api/admin/places/{place} | AdminPlaceDestroyController→SoftDelete/HardDelete | 03§8 | | AdminPlaceDeleteTest | | | ⬜ |
| 36 | GET /api/admin/places/{p}/managers | AdminPlaceManagerIndexController | 03§8 | | AdminPlaceManagerTest | | | ⬜ |
| 37 | POST /api/admin/places/{p}/managers | AdminPlaceManagerStoreController→CreatePlaceManager | 03§8 | | AdminPlaceManagerTest | | | ⬜ |
| 38 | POST /api/admin/places/{p}/managers/{u}/resend | AdminPlaceManagerResendController→ResendPlaceManagerSetup | 03§8 | | AdminPlaceManagerTest | | | ⬜ |
| 39 | DELETE /api/admin/places/{p}/managers/{u} | AdminPlaceManagerRevokeController | 03§8 | | AdminPlaceManagerTest | | | ⬜ |

## B. Cross-cutting concerns (dễ miss nhất — không nằm trong route)

| # | Quan tâm | Nguồn | Knowledge | Spring | Kiểm chứng | Trạng thái |
|---|----------|-------|-----------|--------|------------|------------|
| C1 | Envelope success/error | ApiResponse | GlobalResponseAdvice | ApiResponse envelope asserted in AuthSecurityContractTest + integration tests | ✅ |
| C2 | Exception→HTTP mapping | ApiExceptionHandler | RestControllerAdvice | 422/401/403/429/404/409/500 asserted across integration tests | ✅ |
| C3 | Role middleware từ DB | EnsureUserHasRole | BearerAuthFilter + RoleCheckInterceptor + @RequireRole | RoleMiddlewareTest port: AuthFlowIntegrationTest#roleRevocationAppliesToNextRequest | ✅ |
| C4 | Throttle từng endpoint | routes/api.php | RateLimitService + RateLimitFilter (Bucket4j core) | AuthSecurityContractTest#loginIsRateLimitedAtTheBoundary + RateLimitServiceTest | ✅ |
| C5 | Pagination meta format | LengthAwarePaginator→meta | 01§10 | Page serialization | search/bookmark/visit tests | ⬜ |
| C6 | SHA-256 token hash tương thích Sanctum | config/sanctum + DB | TokenCodec + AccessTokenService (access_tokens) | opaque Spring token verified (Q17); Sanctum shared-DB parity n/a (không chạy chung DB) | ✅ |
| C7 | BCrypt rounds=12 tương thích hash cũ | Hash::check | BCryptPasswordEncoder(12) | integration: Laravel-style $2y$ hash verify + register/login flow | ✅ |
| C8 | day_of_week 2..8 | UpdateAdminPlaceRequest | 03§9 | Validator | regression test port | ⬜ |
| C9 | visit_date Asia/Ho_Chi_Minh | RecordVisit | 03§6 | ZoneId | VisitTest port | ⬜ |
| C10 | Anonymous SHA-256 hash, không lưu IP | RecordVisit | 03§6 | AnonVisitService | VisitTest port | ⬜ |
| C11 | lockForUpdate token one-time | EmailVerification/AccountSetup repos | @Lock(PESSIMISTIC_WRITE) in EmailVerification/AccountSetup repos | accountSetupConsumesTokenOnce + verifyAlreadyVerifiedReturns409WithoutConsuming; multi-thread test defer Phase 3 | ✅ |
| C12 | Google exchange code TTL 60s, pull-one-time | HandleGoogleCallback | OAuthCacheConfiguration (Caffeine 60s) + exchangeCache | googleExchangeCodeIsSingleUseAndCookieProtected | ✅ |
| C13 | Username retry 3 lần khi unique violation | HandleGoogleCallback::createUser | GoogleAuthFlowService retry loop + UsernameGenerator | UsernameGeneratorTest + retry loop parity with Laravel | ✅ |
| C14 | Anti user-enumeration dummy hash | AuthenticateCredentials | AuthenticateCredentialsService (DUMMY_HASH constant) | dummy-hash path exercised by loginWithWrongCredentialsReturns401 | ✅ |
| C15 | is_verified filter public endpoints | ShowPlace/Search/Discovery | 03§3-4 | Repository queries | DiscoveryRandomTest port | ⬜ |
| C16 | Soft-delete ẩn khỏi public | SoftDeletes Eloquent | 03§9 | @SQLRestriction/`deleted_at IS NULL` | DeleteTest port | ⬜ |
| C17 | Rating default 5.0 + check constraint | migration 000032/000033 | 02§places | Flyway + entity default | model test | ⬜ |
| C18 | Optional bearer trên endpoint public | PlaceShow/Visit/Discovery | 04 | OptionalAuthResolver | PlaceDetailTest port | ⬜ |
| C19 | Email views (verify + account setup) | resources/views/emails | Thymeleaf templates (LoggingAuthMailer) | templates render in log mode; SMTP thật deferred | ✅ |
| C20 | Seeders: roles/districts/categories/tags | database/seeders | Flyway V3__auth_roles.sql (roles) | roles seeded; tests query roles table | ✅ |

## C. Symbols chưa có endpoint (kiểm kê .codegraph cuối Phase 2)

Chạy `codegraph_explore` với từng file Action/Service để xác nhận đã xử lý:

| Symbol | Hành động dự kiến | Trạng thái |
|--------|-------------------|------------|
| `PlaceCsvImportSeeder`, `CsvPlaceReader`, `OpenAiCompatibleClient`, `PlaceImportPrompt`, `PlaceDuplicateDetector`, `PlaceImportOutputValidator`, `PlaceImportPersistence`, `TaxonomyProvider` | dev-only import tool — **hỏi người dùng: port / loại / giữ lại Laravel** | ⬜ |
| `CreateAdminAccount` (bootstrap admin) | port + test → `BootstrapAdminService` + `BootstrapAdminRunner` (opt-in env) | ✅ |
| Models planned (`Review`, `Comment`, `PlaceRequest`, `PromotionRequest`, `ModerationAction`, `NotificationDelivery`) | chỉ port schema + JPA entity, chưa business logic | ⬜ |
| Enums chưa dùng endpoint (`ReviewStatus`, `CommentStatus`, `ModerationAction`, `NotificationType/Status`, `PlaceRequestStatus`, `PromotionRequestStatus`) | port thành Java enum | ⬜ |

## D. Định dạng cập nhật

- `⬜ pending` → `🟨 in_progress` → `✅ done` (done = test Spring pass + parity đạt).
- Cột "Parity" điền: `replay OK` / `diff: <mô tả>` / `n/a`.
- Không xóa hàng; nếu quyết định KHÔNG port một mục, chuyển `🚫 skipped` kèm lý do + ngày + người duyệt.
