# HNaj Workspace Instructions

## Nguồn chuẩn
- Nghiệp vụ: `docs/PRD_TECH_SPEC.md`.
- HTTP API: `docs/api/openapi.yaml`.
- Recommendation: `docs/api/recommendation-policy.md`.
- Database: migrations; `docs/database/` là tài liệu diễn giải.
- Nếu nguồn chuẩn xung đột, dừng và báo lại. Không tự tạo route, field, table, biến môi trường hoặc dependency.

## Cách làm
- Đọc code sở hữu hành vi trước khi sửa. Thay đổi nhỏ, đúng nguyên nhân và giữ public API nếu yêu cầu không đổi contract.
- API thay đổi phải đồng bộ OpenAPI và `docs/api/CHANGELOG.md`; recommendation thay đổi phải đồng bộ policy.
- Schema thay đổi phải đồng bộ migration, ERD và data dictionary.
- Muốn thêm dependency phải nêu package, lý do, phương án thay thế và chờ phê duyệt.
- Không bắt buộc tạo hoặc chạy unit test/feature test. Kiểm chứng bằng syntax, lint, build, route/contract check và thao tác runtime phù hợp với thay đổi.

## An toàn dữ liệu
- MySQL runtime và mọi record hiện có là dữ liệu người dùng. Không xóa, reset, truncate, recreate, refresh, reseed hoặc overwrite nếu người dùng chưa xác nhận đúng thao tác và đúng database.
- Trước lệnh có thể ghi dữ liệu, nêu connection/database đích và phân loại read-only, additive hoặc destructive. Thao tác destructive cần xác nhận riêng.
- Không lưu credentials thật trong code, docs, migration, seeder hoặc shell history.

## Runtime và kiểm chứng
- Mọi lệnh PHP/Composer/npm chạy trong Docker. Kiểm tra `docker compose ps` trước.
- Backend: `docker compose exec -T be <command>`; frontend: `docker compose exec -T fe <command>`.
- Không chạy `php`, `php artisan`, `composer` hoặc `npm` trên host. Host chỉ dùng đọc file, Git và static checks không cần dependency.
- Backend: chạy PHP syntax, Pint theo file/phạm vi và `route:list` khi route thay đổi.
- Frontend: chạy lint, build và kiểm tra luồng thật trên browser khi thay đổi UI.
- Báo cáo cuối bằng tiếng Việt, nêu thay đổi, kiểm chứng và rủi ro còn lại.
