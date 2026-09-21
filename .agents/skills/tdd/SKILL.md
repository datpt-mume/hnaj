---
name: tdd
description: "Phát triển hướng kiểm thử (Test-Driven Development). Áp dụng khi người dùng muốn xây dựng tính năng hoặc sửa lỗi theo quy trình viết test trước, nhắc tới 'red-green-refactor' hoặc muốn viết integration tests."
---

# Phát Triển Hướng Kiểm Thử (Test-Driven Development - TDD)

TDD là vòng lặp **Đỏ (Red) → Xanh (Green)**. Kỹ năng này đóng vai trò là tài liệu tham chiếu chuẩn để đảm bảo vòng lặp sinh ra những bài test thực sự có giá trị lâu dài: định nghĩa thế nào là test tốt, đặt test ở đâu, các phản mẫu (anti-patterns) cần tránh, và các quy tắc bất biến của vòng lặp. Cần tham khảo các nguyên tắc này **trước và trong khi code**, tuyệt đối không phải sau khi đã code xong.

Khi khám phá codebase, hãy đọc `CONTEXT.md` (nếu có) để tên bài test và từ vựng giao diện khớp với ngôn ngữ nghiệp vụ của dự án, và tuân thủ các quyết định kiến trúc (ADR) liên quan.

## Thế nào là một bài test tốt (What a good test is)

Bài test phải **kiểm chứng hành vi thông qua giao diện công khai (public interface)**, không kiểm chứng chi tiết triển khai bên trong. Mã nguồn bên trong có thể thay đổi hoàn toàn; bài test thì không nên bị gãy. Một bài test tốt đọc như một bản đặc tả: `"người dùng có thể thanh toán với giỏ hàng hợp lệ"` nói chính xác tính năng gì đang tồn tại, và nó sống sót qua mọi đợt tái cấu trúc vì nó không quan tâm cấu trúc bên trong được cài đặt ra sao.

Xem [tests.md](tests.md) để xem ví dụ mẫu và [mocking.md](mocking.md) để nắm quy tắc mock.

## Khớp nối (Seams): Nơi bài test được đặt vào

Một **khớp nối (seam)** là ranh giới công khai mà bạn dùng để kiểm thử: giao diện nơi bạn quan sát hành vi hệ thống mà không cần chọc vào ruột bên trong. Bài test phải nằm tại các seam, không bao giờ viết bám vào chi tiết nội bộ.

**Chỉ viết test tại các seam đã được thống nhất trước.** Trước khi viết bất kỳ bài test nào, hãy liệt kê các seam cần test và xác nhận với người dùng. Không viết test ở những seam chưa được xác nhận. Bạn không thể test tất cả mọi thứ, do đó việc thống nhất trước các seam là cách để tập trung nỗ lực kiểm thử vào các luồng quan trọng và logic phức tạp thay vì sa đà vào từng trường hợp biên vụn vặt.

Hãy hỏi: *"Giao diện công khai là gì, và chúng ta nên kiểm thử ở những seam nào?"*

Khi hình dạng của giao diện còn chưa rõ (module sâu hay nông, seam nên đặt ở đâu, interface cần phơi ra những gì), hãy tham chiếu kỹ năng `codebase-design`. Đó là nơi cung cấp bộ từ vựng chuẩn về module, interface, depth, seam, adapter, leverage và locality.

## Các phản mẫu kiểm thử cần tránh (Anti-patterns)

- **Bám chặt vào chi tiết triển khai (Implementation-coupled):** Lạm dụng mock các thành phần nội bộ, test các private method, hoặc kiểm chứng qua kênh phụ (truy vấn thẳng database thay vì gọi qua interface). Dấu hiệu nhận biết: bài test bị gãy khi bạn tái cấu trúc code mặc dù hành vi chức năng không hề thay đổi.
- **Tự chứng minh vô nghĩa (Tautological):** Giá trị kỳ vọng trong câu lệnh `expect` được tính toán lại y hệt như cách code chính tính toán (ví dụ: `expect(add(a, b)).toBe(a + b)`), khiến bài test luôn luôn pass và không bao giờ phát hiện được lỗi logic. Giá trị kỳ vọng bắt buộc phải đến từ nguồn độc lập: giá trị literal chuẩn, ví dụ tính tay từ trước, hoặc spec.
- **Cắt lát ngang (Horizontal slicing):** Viết hàng loạt tất cả các bài test trước, rồi mới viết mã triển khai sau. Viết test hàng loạt như vậy chỉ kiểm chứng những hành vi *tưởng tượng*: bạn chỉ test *hình dáng bên ngoài* thay vì hành vi thực tế của người dùng, khiến test không nhạy với thay đổi thực tế và tự trói mình vào cấu trúc test trước khi hiểu rõ cách triển khai. **Hãy làm việc theo lát cắt dọc (vertical slices): một test $\rightarrow$ một phần code triển khai tương ứng $\rightarrow$ lặp lại**, mỗi bài test là một **đạn vạch đường (tracer bullet)** phản hồi dựa trên những gì vòng lặp trước đã mang lại.

## Các quy tắc của vòng lặp (Rules of the loop)

- **Đỏ trước Xanh (Red before green):** Luôn viết test thất bại (Red) trước, sau đó chỉ viết vừa đủ lượng code để test đó vượt qua (Green). Tuyệt đối không suy đoán viết trước code cho các bài test tương lai hoặc thêm các tính năng phỏng đoán.
- **Mỗi lần một lát cắt:** Mỗi chu kỳ chỉ xử lý: một seam, một bài test, một phần code triển khai tối thiểu.
- **Tái cấu trúc (Refactoring) không nằm trong vòng lặp này:** Tái cấu trúc thuộc về giai đoạn review (xem kỹ năng `code-review`), không nằm trong chu kỳ triển khai Đỏ $\rightarrow$ Xanh.
