# Thiết Kế Hai Lần (Design It Twice)

Khi người dùng muốn khám phá các phương án giao diện thay thế cho một ứng viên làm sâu đã chọn, hãy sử dụng mô hình sub-agent song song này. Dựa trên nguyên lý "Design It Twice" của John Ousterhout (*A Philosophy of Software Design*): **Ý tưởng đầu tiên của bạn hiếm khi là ý tưởng tốt nhất.**

Sử dụng bộ từ vựng trong [SKILL.md](SKILL.md): **module**, **interface**, **seam**, **adapter**, **leverage**.

## Quy trình thực hiện

### 1. Định hình không gian bài toán (Frame the problem space)

Trước khi khởi tạo các sub-agent, hãy viết một bản giải thích không gian bài toán cho người dùng đối với ứng viên đã chọn:

- Các ràng buộc mà bất kỳ interface mới nào cũng cần phải thỏa mãn.
- Các phụ thuộc mà nó sẽ dựa vào, và chúng thuộc nhóm phân loại nào (xem [DEEPENING.md](DEEPENING.md)).
- Một đoạn phác thảo mã nguồn minh họa sơ bộ để cụ thể hóa các ràng buộc (đây không phải là đề xuất giải pháp, chỉ là cách làm cụ thể các ràng buộc).

Trình bày điều này cho người dùng, sau đó tiến hành ngay sang Bước 2. Người dùng sẽ đọc và suy ngẫm trong khi các sub-agent làm việc song song trong nền.

### 2. Khởi tạo các Sub-Agent chạy song song

Khởi tạo từ 3 sub-agent trở lên chạy song song. Mỗi sub-agent phải đưa ra một thiết kế interface **hoàn toàn khác biệt** cho module được làm sâu.

Cung cấp cho mỗi sub-agent một bản tóm tắt kỹ thuật riêng (đường dẫn file, chi tiết phụ thuộc, nhóm phân loại phụ thuộc từ [DEEPENING.md](DEEPENING.md), những gì nằm sau seam). Đặt cho mỗi agent một ràng buộc thiết kế khác nhau:

- **Agent 1:** "Tối thiểu hóa giao diện: nhắm tới tối đa 1–3 điểm truy cập (entry points). Tối đa hóa đòn bẩy (leverage) trên mỗi entry point."
- **Agent 2:** "Tối đa hóa tính linh hoạt: hỗ trợ nhiều use cases và khả năng mở rộng trong tương lai."
- **Agent 3:** "Tối ưu hóa cho caller phổ biến nhất: làm cho trường hợp mặc định trở nên cực kỳ đơn giản."
- **Agent 4 (nếu áp dụng):** "Thiết kế xoay quanh Ports & Adapters cho các phụ thuộc xuyên qua khớp nối (cross-seam dependencies)."

Yêu cầu mỗi sub-agent xuất ra:
1. Giao diện đề xuất (kiểu dữ liệu, phương thức, tham số, các bất biến, thứ tự gọi, chế độ lỗi).
2. Ví dụ sử dụng minh họa cách callers gọi nó.
3. Những gì phần triển khai giấu kín đằng sau khớp nối (seam).
4. Chiến lược phụ thuộc và các adapter (xem [DEEPENING.md](DEEPENING.md)).
5. Đánh đổi (Trade-offs): Nơi nào đòn bẩy cao, nơi nào đòn bẩy còn mỏng.

### 3. Trình bày và so sánh (Present and compare)

Trình bày các thiết kế lần lượt để người dùng tiếp thu từng phương án, sau đó so sánh chúng bằng văn bản. Đối chiếu dựa trên:
- **Độ sâu (Depth):** Đòn bẩy tại giao diện.
- **Tính cục bộ (Locality):** Nơi các thay đổi tập trung.
- **Vị trí đặt khớp nối (Seam placement).**

Sau khi so sánh, hãy đưa ra khuyến nghị rõ ràng của chính bạn: phương án nào bạn đánh giá là mạnh nhất và vì sao. Nếu các yếu tố từ các phương án khác nhau có thể kết hợp tốt, hãy đề xuất một phương án lai (hybrid). Hãy có chính kiến rõ ràng: người dùng cần một lời tư vấn sắc bén, không phải một danh sách thực đơn phân vân.
