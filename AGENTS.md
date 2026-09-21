# Hướng Dẫn Cho Agent (Agent Guidelines)

Tài liệu này là mục lục điều hướng trung tâm (Central Router) dành cho các AI Coding Assistant hoạt động trong repository này.

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

## 3. Quản lý công việc (Issue Tracker)
- Toàn bộ task và đặc tả tính năng được theo dõi dưới dạng các file markdown cục bộ tại `.scratch/<feature>/`.
- Chi tiết quy ước xem tại [docs/agents/issue-tracker.md](docs/agents/issue-tracker.md).

## 4. Tài liệu nghiệp vụ (Domain Docs)
- Dự án áp dụng cấu trúc đơn ngữ cảnh (single-context) với file `CONTEXT.md` tại thư mục gốc và các tài liệu quyết định kiến trúc (ADR) trong `docs/adr/`.
- Chi tiết quy ước xem tại [docs/agents/domain.md](docs/agents/domain.md).

## 5. Kỹ năng Agent (Agent Skills)
Khi thực hiện các giai đoạn trong quy trình phát triển, hãy kích hoạt các kỹ năng tương ứng:
- **Phỏng vấn chốt thiết kế:** `/grill-me`
- **Chia nhỏ task dạng lát cắt dọc (Tracer Bullets):** `/to-tickets`
- **Triển khai code theo TDD:** `/implement` và `/tdd`
- **Đánh giá mã nguồn 2 trục (Tiêu chuẩn & Đặc tả):** `/code-review`
- **Chẩn đoán lỗi khó:** `/diagnosing-bugs`
- **Tối ưu hóa kiến trúc module sâu:** `/codebase-design` và `/improve-codebase-architecture`
