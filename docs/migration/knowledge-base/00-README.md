# Knowledge Base — Migration HNAJ Laravel → Java Spring Boot

> Mục đích: lưu **toàn bộ tri thức nghiệp vụ** của backend Laravel hiện tại thành tài liệu tường minh,
> làm nguồn sự thật duy nhất cho quá trình triển khai backend Java Spring Boot bằng AI,
> nhằm **không miss cases / không miss knowledge** khi chuyển đổi.

## Vì sao cần bộ tài liệu này?

`.codegraph` là công cụ index cấu trúc code (symbol, call graph) — tốt để **điều hướng và kiểm kê**,
nhưng **không đủ** để chống miss vì nó không nắm được: hành vi ngầm của framework (Eloquent casts,
Sanctum guard, middleware ordering), config runtime (throttle, TTL cache, timezone), ràng buộc
business rule nằm trong comment/quyết định, và edge case chỉ có trong test. Bộ knowledge base này
bù đắp các khoảng trống đó. `.codegraph` vẫn được dùng ở bước đối chiếu cuối mỗi phase (xem `06`).

## Cấu trúc

| File | Nội dung | Vai trò chống miss |
|------|----------|--------------------|
| [`00-inventory-endpoints.md`](./00-inventory-endpoints.md) | 39 endpoint + throttle + auth + domain mapping | Checklist "đã port đủ endpoint chưa" |
| [`01-api-contract.md`](./01-api-contract.md) | Response envelope, error codes, exception→HTTP mapping | Giữ hợp đồng API cho FE không đổi |
| [`02-schema.md`](./02-schema.md) | Toàn bộ bảng/cột/constraint/index từ 38 migration | Nền cho Flyway, không miss cột/ràng buộc |
| [`03-domain-rules.md`](./03-domain-rules.md) | Business rule từng use case (transaction, idempotency, scoring, timezone) | Phần dễ miss nhất khi đọc code lướt |
| [`04-auth-security.md`](./04-auth-security.md) | Sanctum token, role-from-DB, hashing, cache, rate limit | Bảo mật không được suy giảm khi port |
| [`05-test-catalog.md`](./05-test-catalog.md) | 239 tests → behavioral spec, cách port | Test = sự thật hành vi |
| [`06-traceability-matrix.md`](./06-traceability-matrix.md) | Endpoint × Knowledge × Spring × Test, trạng thái | **Công cụ chính** để chứng minh không miss |
| [`07-open-questions.md`](./07-open-questions.md) | Quyết định cần người dùng chốt | Không tự đoán |

## Cách dùng trong workflow migration

1. **Trước khi port một domain:** đọc mục tương ứng trong `03` + `01` + dòng liên quan trong `06`.
2. **Trong khi port:** điền cột "Spring class" trong `06`.
3. **Sau khi port:** chạy test Laravel baseline + test Spring port + parity replay; cập nhật cột Test/Parity/Trạng thái.
4. **Cuối phase:** dùng `codegraph_explore` đối chiếu symbol Laravel trong `06` mục C — đảm bảo không sót.
5. **Nguyên tắc (AGENTS.md §14):** không tự chọn version/tool, không sửa test để che lỗi, không tự commit.

## Nguồn tham chiếu động (khi knowledge base stale)

Knowledge base là **snapshot tại thời điểm tạo**. Nếu code Laravel thay đổi sau đó, phải cập nhật
tương ứng hoặc re-run trích xuất. Nguồn gốc luôn là:
- [`hnaj-be/routes/api.php`](../../../hnaj-be/routes/api.php) — endpoints
- [`hnaj-be/app/`](../../../hnaj-be/app/) — logic
- [`hnaj-be/database/migrations/`](../../../hnaj-be/database/migrations/) — schema
- [`plans/project-progress.md`](../../../plans/project-progress.md) — quyết định nghiệp vụ đã chốt
- [`AGENTS.md`](../../../AGENTS.md) — quy tắc kiến trúc & quy trình

## Ngày tạo snapshot: 2026-09-05 · Backend Laravel tại commit hiện tại của workspace.
