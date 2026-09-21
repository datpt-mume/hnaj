# Tiêu Chuẩn Lập Trình (Coding Standards)

Tài liệu này quy định các chuẩn mực viết mã nguồn áp dụng cho toàn bộ dự án. Mọi mã nguồn do AI hoặc kỹ sư tạo ra đều phải tuân thủ nghiêm ngặt các nguyên tắc dưới đây:

## 1. Triết lý Thiết kế (Design Principles)
- **Deep Modules (Module sâu):** Luôn ưu tiên thiết kế module có giao diện công khai (interface) nhỏ gọn, đơn giản nhưng logic bên trong phong phú. Tránh tạo ra các module nông (shallow modules) chỉ làm nhiệm vụ chuyển tiếp dữ liệu (pass-through).
- **Single Responsibility (Trách nhiệm duy nhất):** Mỗi hàm hoặc module chỉ làm tốt một việc duy nhất và có lý do duy nhất để thay đổi.
- **Explicit over Implicit:** Rõ ràng luôn tốt hơn ngầm định. Tránh các "ma thuật" ẩn hoặc logic phụ thuộc ngầm.

## 2. Tiêu chuẩn TypeScript / JavaScript
- **Strict Typing:** Luôn bật chế độ strict. **TUYỆT ĐỐI KHÔNG DÙNG `any`**. Sử dụng `unknown` nếu chưa rõ kiểu và ép kiểu an toàn (type narrowing/guards).
- **Immutability:** Ưu tiên dữ liệu bất biến (`const`, `readonly`, spread operator thay vì mutate trực tiếp object/array).
- **Naming Conventions:**
  - `camelCase`: Tên biến, hàm, thuộc tính object.
  - `PascalCase`: Tên Class, Type, Interface, React Component.
  - `UPPER_SNAKE_CASE`: Hằng số toàn cục (constants).
  - Tên biến/hàm phải thể hiện đúng bản chất (tránh viết tắt tối nghĩa như `tmp`, `data`, `res1`).

## 3. Xử lý Lỗi (Error Handling)
- Luôn xử lý lỗi rõ ràng, không nuốt lỗi (silent error / empty catch block).
- Với các hàm bất đồng bộ (async/await), luôn bọc trong khối `try/catch` có ngữ cảnh hoặc trả về kiểu Result `{ success: boolean, data?: T, error?: Error }`.
- Thông báo lỗi phải rõ ràng, chỉ rõ nguyên nhân và cách khắc phục nếu có thể.

## 4. Cấu trúc File & Code Cleanliness
- Giữ các file ngắn gọn, dễ đọc (lý tưởng dưới 200–300 dòng code). Nếu file quá dài, hãy tìm cơ hội tách thành các sub-module hoặc helper riêng.
- Xóa bỏ toàn bộ `console.log` debug, code thừa hoặc comment không còn giá trị trước khi hoàn thành task.
