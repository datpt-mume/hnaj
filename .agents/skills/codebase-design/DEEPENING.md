# Làm Sâu Module (Deepening)

Cách làm sâu một cụm module nông một cách an toàn dựa trên các phụ thuộc của nó. Giả định bạn đã nắm bộ từ vựng trong [SKILL.md](SKILL.md): **module**, **interface**, **seam**, **adapter**.

## Phân loại phụ thuộc (Dependency categories)

Khi đánh giá một ứng viên để làm sâu, hãy phân loại các phụ thuộc của nó. Nhóm phụ thuộc sẽ quyết định cách module sau khi làm sâu được kiểm thử qua khớp nối (seam) của nó:

### 1. Nội trình (In-process)

Tính toán thuần túy, trạng thái trong bộ nhớ, không có I/O. Luôn luôn có thể làm sâu được: gộp các module lại và kiểm thử trực tiếp thông qua interface mới. Không cần adapter.

### 2. Có thể thay thế cục bộ (Local-substitutable)

Các phụ thuộc có bản sao thay thế cục bộ khi test (như PGLite cho Postgres, hệ thống file in-memory). Có thể làm sâu được nếu bản sao thay thế tồn tại. Module được kiểm thử với bản thay thế chạy ngay trong test suite. Seam nằm ở bên trong nội bộ; không cần tạo port ở interface bên ngoài.

### 3. Từ xa nhưng thuộc quyền sở hữu (Remote but owned - Ports & Adapters)

Các dịch vụ của chính bạn nằm qua ranh giới mạng (microservices, internal APIs). Định nghĩa một **port (interface)** tại seam. Module sâu sở hữu logic nghiệp vụ; tầng giao vận mạng được tiêm vào dưới dạng một **adapter**. Khi test dùng adapter in-memory. Khi chạy Production dùng adapter HTTP/gRPC/Message Queue.

*Mẫu khuyến nghị: "Định nghĩa một port tại seam, triển khai HTTP adapter cho môi trường production và in-memory adapter cho môi trường test, để toàn bộ logic nằm trọn trong một module sâu duy nhất dù nó được triển khai qua mạng."*

### 4. Bên thứ ba thực sự (True external - Mock)

Các dịch vụ của bên thứ ba (Stripe, Twilio,...) mà bạn không kiểm soát. Module sâu nhận phụ thuộc bên ngoài dưới dạng một port được tiêm vào; các bài test cung cấp một mock adapter.

## Kỷ luật về khớp nối (Seam discipline)

- **Một adapter nghĩa là seam giả định; hai adapter mới là seam thực sự:** Đừng đưa vào một port trừ khi có ít nhất hai adapter được chứng minh rõ ràng (thường là Production + Test). Một seam chỉ có 1 adapter duy nhất chỉ là sự gián tiếp (indirection) thừa thãi.
- **Khớp nối nội bộ (Internal seams) vs Khớp nối bên ngoài (External seams):** Một module sâu có thể có các seam nội bộ (riêng tư đối với phần triển khai của nó, phục vụ bài test nội bộ) bên cạnh seam bên ngoài tại interface công khai. Đừng phơi bày các seam nội bộ ra ngoài interface chỉ vì các bài test cần dùng tới chúng.

## Chiến lược kiểm thử: Thay thế, không xếp tầng (Replace, don't layer)

- Các bài unit test cũ trên các module nông sẽ trở thành rác thừa thãi một khi bài test tại interface của module sâu đã tồn tại; **hãy xóa bỏ chúng**.
- Viết các bài test mới tại interface của module sâu. **Interface chính là bề mặt kiểm thử (The interface is the test surface)**.
- Các bài test phải khẳng định (assert) trên các kết quả quan sát được thông qua interface, không kiểm tra trạng thái nội bộ.
- Các bài test phải sống sót qua các đợt tái cấu trúc nội bộ, vì chúng mô tả hành vi người dùng, không mô tả chi tiết cài đặt. Nếu một bài test buộc phải sửa khi code triển khai thay đổi, bài test đó đang chọc vượt quá ranh giới interface.
