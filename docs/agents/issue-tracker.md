# Quản Lý Công Việc: File Markdown Cục Bộ (Issue Tracker: Local Markdown)

Các issue và đặc tả (spec) của dự án này được lưu trữ dưới dạng các file markdown trong thư mục `.scratch/`.

## Quy ước (Conventions)

- Mỗi tính năng nằm trong một thư mục riêng: `.scratch/<feature-slug>/`
- File đặc tả tính năng là: `.scratch/<feature-slug>/spec.md`
- Các issue triển khai được lưu thành **từng file riêng biệt cho mỗi ticket** tại `.scratch/<feature-slug>/issues/<NN>-<slug>.md`, đánh số từ `01`, tuyệt đối không gộp chung vào một file duy nhất.
- Trạng thái xử lý được ghi ở dòng `Status:` gần đầu mỗi file (ví dụ: `Status: ready-for-agent`).
- Bình luận và lịch sử trao đổi được nối thêm vào cuối file dưới đề mục `## Comments`.

## Khi một kỹ năng yêu cầu "Publish to the issue tracker"

Tạo một file mới trong thư mục `.scratch/<feature-slug>/` (tự động tạo thư mục nếu chưa có).

## Khi một kỹ năng yêu cầu "Fetch the relevant ticket"

Đọc file tại đường dẫn được tham chiếu. Người dùng thường sẽ truyền trực tiếp đường dẫn file hoặc số hiệu ticket.

## Thao tác định hướng (Wayfinding operations)

Được sử dụng khi lập kế hoạch đường dài:

- **Bản đồ tổng thể (Map)**: `.scratch/<effort>/map.md` (Ghi chú / Quyết định đã chốt / Những phần chưa rõ).
- **Ticket con (Child ticket)**: `.scratch/<effort>/issues/NN-<slug>.md`, đánh số từ `01`, chứa câu hỏi hoặc tác vụ cụ thể trong thân bài. Dòng `Type:` xác định loại (`research`/`prototype`/`grilling`/`task`); dòng `Status:` xác định trạng thái `claimed`/`resolved`.
- **Chặn phụ thuộc (Blocking)**: Dòng `Blocked by: NN, NN` ở đầu file. Một ticket được gỡ chặn khi tất cả các file mà nó liệt kê đã chuyển sang `resolved`.
- **Biên tác vụ (Frontier)**: Quét thư mục `.scratch/<effort>/issues/` để tìm các ticket đang mở, đã gỡ chặn và chưa có ai nhận (`unclaimed`); ticket có số hiệu nhỏ nhất sẽ được ưu tiên làm trước.
- **Nhận việc (Claim)**: Đặt `Status: claimed` và lưu file trước khi bắt đầu code.
- **Hoàn thành (Resolve)**: Thêm câu trả lời / kết quả dưới đề mục `## Answer`, đặt `Status: resolved`, sau đó thêm tóm tắt và liên kết vào danh sách quyết định trong `map.md`.
