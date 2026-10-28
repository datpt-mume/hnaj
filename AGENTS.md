# Hướng Dẫn Cho Agent (Agent Guidelines)

Tài liệu này là mục lục điều hướng trung tâm (Central Router) dành cho các AI Coding Assistant hoạt động trong repository này.

## 0. Ponytail bắt buộc cho mọi thay đổi mã nguồn
- Khi **bất cứ dòng mã nguồn nào** được thêm, sửa hoặc xóa, agent **BẮT BUỘC** phải tuân thủ skill [`ponytail`](./.agents/skills/ponytail/SKILL.md) trong suốt task; không được coi đây là lựa chọn tùy ý.
- Trước khi viết code, phải đọc toàn bộ luồng liên quan và áp dụng ladder Ponytail: không cần tồn tại → tái sử dụng → stdlib/native → dependency đã có → giải pháp ngắn nhất có thể.
- Phải sửa tại nguyên nhân gốc, không thêm abstraction/scaffolding không được yêu cầu, và giữ diff nhỏ nhất nhưng đầy đủ.
- Không được hy sinh validation tại trust boundary, xử lý lỗi, bảo mật, accessibility, tính đúng đắn hoặc yêu cầu người dùng vì mục tiêu giảm dòng code.
- Với logic không tầm thường, phải để lại một kiểm tra có thể chạy được. Khi hoàn tất, báo ngắn gọn phần đã cố ý không làm và điều kiện để cần mở rộng.
- Thay đổi chỉ tài liệu/cấu hình không phải mã nguồn vẫn nên dùng cùng nguyên tắc tối giản; quy định bắt buộc trên áp dụng khi có ít nhất một line code thay đổi.

## 1. Vòng lặp phản hồi bắt buộc (Feedback Loops)
Trước khi báo hoàn thành bất kỳ task nào, bạn BẮT BUỘC phải chạy và đảm bảo vượt qua:
- Kiểm tra kiểu dữ liệu: `npm run typecheck` (hoặc lệnh typecheck của dự án).
- Chạy kiểm thử: `npm test` (toàn bộ bài test phải pass xanh).

## 2. Quy tắc chi tiết theo khu vực (Domain Rules)
Khi làm việc với các phần tương ứng, hãy chủ động đọc file quy tắc chi tiết trước khi viết code:
- **Tiêu chuẩn Lập trình chung:** Xem [.agents/rules/coding-standards.md](.agents/rules/coding-standards.md).
- **Quy ước Git & Commit:** Xem [.agents/rules/git-workflow.md](.agents/rules/git-workflow.md).
- **Quy chuẩn Kiểm thử & TDD:** Xem [.agents/rules/testing.md](.agents/rules/testing.md).
- **Quy tắc An toàn bất biến:** Xem [.agents/rules/safety.md](.agents/rules/safety.md).
- **Quy tắc Ponytail:** Xem [.agents/skills/ponytail/SKILL.md](.agents/skills/ponytail/SKILL.md) và tài liệu preflight khi task cần audit trạng thái: [.agents/skills/ponytail/references/stateful-preflight-audits.md](.agents/skills/ponytail/references/stateful-preflight-audits.md).

## 3. Quản lý công việc (Issue Tracker)
- Toàn bộ task và đặc tả tính năng được theo dõi dưới dạng các file markdown cục bộ tại `.scratch/<feature>/`.
- Chi tiết quy ước xem tại [docs/agents/issue-tracker.md](docs/agents/issue-tracker.md).

## 4. Tài liệu nghiệp vụ (Domain Docs)
- Dự án áp dụng cấu trúc đơn ngữ cảnh (single-context) với file `CONTEXT.md` tại thư mục gốc và các tài liệu quyết định kiến trúc (ADR) trong `docs/adr/`.
- Chi tiết quy ước xem tại [docs/agents/domain.md](docs/agents/domain.md).

## 5. Kỹ năng Agent (Agent Skills)
Khi thực hiện các giai đoạn trong quy trình phát triển, hãy kích hoạt các kỹ năng tương ứng:
- **Ponytail (bắt buộc khi thay đổi code):** [`ponytail`](./.agents/skills/ponytail/SKILL.md)
- **Phỏng vấn chốt thiết kế:** `/grill-me`
- **Chia nhỏ task dạng lát cắt dọc (Tracer Bullets):** `/to-tickets`
- **Triển khai code theo TDD:** `/implement` và `/tdd`
- **Đánh giá mã nguồn 2 trục (Tiêu chuẩn & Đặc tả):** `/code-review`
- **Chẩn đoán lỗi khó:** `/diagnosing-bugs`
- **Dữ liệu mẫu Địa điểm:** [maps.sql](./maps.sql) là SQL dump MySQL dùng làm nguồn dữ liệu thực tế ban đầu cho module Địa điểm; xem chi tiết ở mục **Nguồn dữ liệu Địa điểm** trong [CONTEXT.md](./CONTEXT.md).
