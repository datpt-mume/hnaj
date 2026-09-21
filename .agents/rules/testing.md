# Quy Chuẩn Kiểm Thử (Testing & Quality Assurance)

Tài liệu này quy định các tiêu chuẩn viết test và kiểm soát chất lượng mã nguồn.

## 1. Phương pháp luận cốt lõi: TDD (Test-Driven Development)
Khi phát triển tính năng mới hoặc sửa lỗi, luôn ưu tiên áp dụng chu trình **Đỏ – Xanh – Tái cấu trúc (Red – Green – Refactor)**:
1. **Red (Đỏ):** Viết bài test mô tả hành vi mong muốn trước $\rightarrow$ Chạy test và xác nhận test thất bại vì code chưa tồn tại.
2. **Green (Xanh):** Viết lượng code triển khai tối thiểu để bài test vượt qua.
3. **Refactor (Tái cấu trúc):** Dọn dẹp code, tối ưu hiệu năng và cấu trúc mà bài test vẫn giữ màu xanh.

## 2. Kiểm thử thông qua Giao diện công khai (Public Interfaces)
- **Kiểm tra hành vi (Behavior), KHÔNG kiểm tra chi tiết cài đặt (Implementation):** Bài test phải sống sót qua các đợt refactor nội bộ. Nếu sửa cấu trúc bên trong hàm mà chức năng bên ngoài không đổi, bài test KHÔNG ĐƯỢC PHÉP gãy.
- **Không test các private method:** Private method được kiểm thử gián tiếp thông qua các public method gọi đến nó.
- **Không viết test tự chứng minh vô nghĩa (Tautological):** Giá trị kỳ vọng trong `expect()` phải là giá trị chuẩn độc lập, không phải kết quả tính toán lại bằng cùng công thức của code.

## 3. Nguyên tắc Mocking
- **Chỉ mock tại ranh giới hệ thống (System Boundaries):** Các API bên thứ ba (Stripe, Twilio, gửi Email/SMS), thời gian ngẫu nhiên.
- **KHÔNG mock các class/module do chính dự án sở hữu:** Nếu cần test database, ưu tiên dùng in-memory test database (SQLite in-memory, PGLite) thay vì mock hàm query.

## 4. Vòng lặp phản hồi bắt buộc (Feedback Loop Requirements)
Trước khi báo hoàn thành một task, bắt buộc phải chạy và xác nhận 2 điều kiện:
- [ ] **Typecheck thành công 100%:** Không có bất kỳ lỗi kiểu dữ liệu nào.
- [ ] **Toàn bộ Test Suites vượt qua (Pass 100%):** Không có bài test nào bị fail hoặc skip bất thường.
