# Quy Tắc An Toàn Bất Biến (Safety Constraints)

Tài liệu này định nghĩa các giới hạn an toàn tuyệt đối mà Agent hoặc bất kỳ công cụ tự động nào KHÔNG ĐƯỢC PHÉP vi phạm khi thao tác trên hệ thống:

## 1. Bảo vệ Dữ liệu & Môi trường
- **Cấm tự ý xóa bảng/database:** Tuyệt đối không chạy các lệnh có tính hủy hoại dữ liệu như `DROP DATABASE`, `DROP TABLE`, `rm -rf` các thư mục gốc trừ khi có sự xác nhận rõ ràng của người dùng.
- **Bảo mật Credentials:** Khi in log hoặc hiển thị kết quả, luôn che giấu các khóa bí mật (API Keys, Passwords, Tokens) bằng chuỗi `<REDACTED>`.
- **Cấm ghi đè cấu hình sản xuất:** Tuyệt đối không tự ý sửa đổi hoặc xóa các file biến môi trường gốc (`.env.production`, `.env`).

## 2. Thao tác trên Git an toàn
- Không bao giờ chạy lệnh `git push --force` hoặc `git reset --hard` vào nhánh `main` / `master`.
- Không tự ý commit trực tiếp nếu mã nguồn đang có lỗi linter hoặc lỗi biên dịch chưa được giải quyết.

## 3. Quy tắc Xác nhận với Người dùng (Human-in-the-loop)
- Bất kỳ hành động nào có nguy cơ làm mất dữ liệu, cài đặt các package không rõ nguồn gốc, hoặc thay đổi lớn về kiến trúc hệ thống đều **BẮT BUỘC phải hỏi ý kiến người dùng trước khi thực hiện**.
