# Quy Ước Git & Quản Lý Phiên Bản (Git Workflow)

Tài liệu này quy định các chuẩn mực khi làm việc với Git trong dự án.

## 1. Quy chuẩn Đặt tên Commit (Conventional Commits)
Mỗi commit message phải tuân thủ định dạng chuẩn quốc tế:
```
<type>(<scope>): <mô tả ngắn gọn>
```

Các tiền tố `type` bắt buộc:
- `feat`: Tính năng mới cho người dùng.
- `fix`: Sửa lỗi (bug fix).
- `refactor`: Tái cấu trúc code (không đổi hành vi bên ngoài, không thêm tính năng, không sửa bug).
- `test`: Thêm hoặc chỉnh sửa các bài test.
- `docs`: Thêm hoặc sửa tài liệu, comments.
- `chore`: Thay đổi cấu hình build, dependencies, tooling.

*Ví dụ:*
- `feat(auth): thêm chức năng đăng nhập bằng email OTP`
- `fix(cart): sửa lỗi tính sai tổng tiền khi áp mã giảm giá`
- `test(orders): bổ sung unit test cho luồng hủy đơn hàng`

## 2. Kích thước & Phạm vi Commit
- **Atomic Commits (Commit nguyên tử):** Mỗi commit chỉ nên giải quyết **MỘT việc duy nhất**. Không gộp chung việc sửa bug A vào cùng commit với tính năng B.
- Commit phải luôn ở trạng thái "xanh" (mã nguồn build được và pass toàn bộ test). Tuyệt đối không commit code đang bị gãy hoặc lỗi cú pháp.

## 3. Quản lý Nhánh (Branching Strategy)
- Nhánh chính: `main` (hoặc `master`).
- Tên nhánh tính năng: `feat/<tên-ngắn>`, ví dụ `feat/user-profile`.
- Tên nhánh sửa lỗi: `fix/<tên-ngắn>`, ví dụ `fix/login-redirect`.

## 4. An toàn Dữ liệu (Security)
- **TUYỆT ĐỐI KHÔNG commit các file cấu hình môi trường chứa secret:** `.env`, `.env.local`, file chứa API keys, database credentials.
- Luôn kiểm tra `git status` và `git diff` trước khi commit để đảm bảo không commit nhầm file rác hoặc file tạm.
