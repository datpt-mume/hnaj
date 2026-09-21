---
name: implement
description: "Triển khai một phần việc dựa trên bản đặc tả (spec) hoặc danh sách ticket đã lập."
disable-model-invocation: true
---

# Triển Khai Công Việc (Implement)

Thực hiện việc lập trình và hiện thực hóa phần công việc được mô tả trong bản đặc tả (spec) hoặc các ticket.

1. **Áp dụng TDD:** Sử dụng `/tdd` ở bất cứ nơi nào có thể, tại các khớp nối (seams) đã được thống nhất từ trước.
2. **Kiểm tra liên tục:** Chạy kiểm tra kiểu dữ liệu (typecheck) và chạy từng file test liên quan một cách thường xuyên trong suốt quá trình code. Chỉ chạy toàn bộ test suite một lần duy nhất vào lúc cuối cùng.
3. **Đánh giá mã nguồn:** Sau khi hoàn thành việc triển khai, sử dụng `/code-review` để tự động rà soát lại mã nguồn vừa viết theo 2 trục Tiêu chuẩn và Đặc tả.
4. **Cam kết mã nguồn:** Commit các thay đổi hoàn chỉnh vào branch hiện tại.
