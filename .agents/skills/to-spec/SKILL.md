---
name: to-spec
description: "Chuyển cuộc hội thoại hiện tại thành một bản đặc tả yêu cầu (spec/PRD) và lưu vào issue tracker của dự án: không phỏng vấn thêm, chỉ tổng hợp lại những gì đã thảo luận."
disable-model-invocation: true
---

# Tạo Bản Đặc Tả (To Spec)

Kỹ năng này sử dụng ngữ cảnh hội thoại hiện tại và sự hiểu biết về codebase để tạo ra một bản đặc tả (spec/PRD). **KHÔNG phỏng vấn thêm người dùng**; chỉ tổng hợp lại những gì bạn đã nắm được.

Hệ thống quản lý issue và từ vựng nhãn phân loại (triage label) cần được cung cấp trước. Nếu chưa, hãy yêu cầu người dùng chạy `/setup-matt-pocock-skills`.

## Quy trình thực hiện

1. **Khám phá codebase:** Nếu chưa làm, hãy khám phá repository để nắm được hiện trạng mã nguồn. Sử dụng thuật ngữ nghiệp vụ (domain glossary) xuyên suốt bản đặc tả và tuân thủ các quyết định kiến trúc (ADR) trong khu vực liên quan.

2. **Xác định các khớp nối kiểm thử (seams):** Phác thảo các khớp nối (seams) mà bạn sẽ dùng để kiểm thử tính năng. Ưu tiên sử dụng các seam đã có sẵn trong hệ thống thay vì tạo seam mới. Sử dụng seam ở tầng cao nhất có thể. Càng ít seam trên toàn bộ codebase càng tốt — con số lý tưởng là 1 seam duy nhất cho mỗi tính năng.
   
   Xác nhận lại với người dùng xem các seam này có đúng kỳ vọng của họ không.

3. **Viết bản đặc tả:** Sử dụng cấu trúc mẫu bên dưới, sau đó xuất bản vào issue tracker của dự án. Gán nhãn `ready-for-agent`.

<spec-template>

## Mô tả vấn đề (Problem Statement)

Vấn đề mà người dùng đang gặp phải, mô tả từ góc nhìn của chính người dùng.

## Giải pháp (Solution)

Giải pháp giải quyết vấn đề đó, mô tả từ góc nhìn của người dùng.

## Câu chuyện người dùng (User Stories)

Danh sách ĐẦY ĐỦ và được đánh số các user story. Mỗi câu chuyện phải tuân theo cấu trúc chuẩn:

1. Là một <vai trò / actor>, tôi muốn <tính năng>, để <lợi ích mang lại>

<user-story-example>
1. Là một khách hàng dùng ứng dụng ngân hàng di động, tôi muốn xem số dư tài khoản của mình, để tôi có thể đưa ra quyết định chi tiêu sáng suốt hơn.
</user-story-example>

Danh sách user stories này cần cực kỳ toàn diện và bao quát mọi khía cạnh của tính năng.

## Quyết định triển khai (Implementation Decisions)

Danh sách các quyết định kỹ thuật đã thống nhất. Có thể bao gồm:

- Các module sẽ được xây dựng mới hoặc chỉnh sửa.
- Giao diện (interfaces) của các module đó sẽ được sửa đổi ra sao.
- Các làm rõ về mặt kỹ thuật từ phía developer.
- Quyết định kiến trúc hệ thống.
- Thay đổi lược đồ dữ liệu (schema changes).
- Ràng buộc và hợp đồng API (API contracts).
- Các tương tác cụ thể.

TUYỆT ĐỐI KHÔNG đưa vào đường dẫn file chi tiết hoặc các đoạn code vụn vặt vì chúng sẽ rất nhanh lỗi thời.

Ngoại lệ: Nếu bản prototype tạo ra được đoạn code thể hiện quyết định chính xác hơn lời văn (như state machine, reducer, schema, kiểu dữ liệu), hãy đính kèm phần code quan trọng đó và ghi chú ngắn gọn nguồn từ prototype.

## Quyết định kiểm thử (Testing Decisions)

Danh sách các quyết định kiểm thử đã chốt, bao gồm:

- Tiêu chí thế nào là một bài test tốt (chỉ kiểm tra hành vi quan sát được từ bên ngoài, không kiểm tra chi tiết triển khai nội bộ).
- Module nào sẽ được kiểm thử.
- Các bài test mẫu tương tự đã có sẵn trong codebase để tham khảo (prior art).

## Nằm ngoài phạm vi (Out of Scope)

Mô tả rõ những tính năng/vấn đề KHÔNG xử lý trong phạm vi bản đặc tả này.

## Ghi chú thêm (Further Notes)

Bất kỳ ghi chú bổ sung nào khác về tính năng.

</spec-template>
