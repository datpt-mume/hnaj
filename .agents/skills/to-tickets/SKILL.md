---
name: to-tickets
description: "Chia nhỏ kế hoạch, spec hoặc cuộc hội thoại hiện tại thành tập hợp các ticket dạng lát cắt dọc (tracer bullets), khai báo rõ các phụ thuộc chặn (blocking edges) và lưu vào issue tracker được cấu hình (file cục bộ hoặc issue tracker như GitHub/Linear)."
disable-model-invocation: true
---

# Tạo Ticket Công Việc (To Tickets)

Bẻ gãy kế hoạch, đặc tả (spec) hoặc ngữ cảnh hội thoại hiện tại thành tập hợp các **ticket**: mỗi ticket là một lát cắt dọc (vertical slice) dạng đạn vạch đường (tracer bullet), khai báo rõ danh sách các ticket **chặn (block)** nó.

Hệ thống quản lý issue và từ vựng nhãn phân loại (triage label) cần được cấu hình trước. Nếu chưa, hãy hướng dẫn người dùng chạy `/setup-matt-pocock-skills`.

## Quy trình thực hiện

### 1. Thu thập ngữ cảnh (Gather context)

Lấy thông tin từ bất kỳ nội dung nào đã có trong ngữ cảnh hội thoại. Nếu người dùng truyền một tham chiếu (đường dẫn spec, số issue hoặc URL), hãy đọc toàn bộ nội dung và các bình luận của nó.

### 2. Khám phá codebase (Tùy chọn)

Nếu bạn chưa khám phá codebase, hãy đọc qua để nắm được hiện trạng mã nguồn. Tiêu đề và mô tả của ticket phải sử dụng thuật ngữ nghiệp vụ (domain glossary) của dự án và tuân thủ các quyết định kiến trúc (ADR) liên quan.

Tìm cơ hội để tái cấu trúc dọn đường (prefactor) giúp việc triển khai sau đó dễ dàng hơn: *"Hãy làm cho việc thay đổi trở nên dễ dàng trước, sau đó mới thực hiện thay đổi dễ dàng đó."*

### 3. Phác thảo các lát cắt dọc (Draft vertical slices)

Chia nhỏ công việc thành các ticket dạng **đạn vạch đường (tracer bullet)**.

<vertical-slice-rules>

- Mỗi lát cắt phải đi xuyên suốt một đường hẹp nhưng HOÀN CHỈNH qua mọi tầng hệ thống (schema, API, UI, tests): là lát cắt dọc (vertical slice), TUYỆT ĐỐI KHÔNG cắt ngang theo từng tầng (horizontal slice).
- Một lát cắt khi hoàn thành phải có khả năng demo chạy được hoặc kiểm chứng độc lập được.
- Kích thước mỗi lát cắt phải vừa vặn trong một cửa sổ ngữ cảnh (context window) mới toanh.
- Mọi công việc tái cấu trúc dọn đường (prefactoring) phải được đưa lên làm trước.

</vertical-slice-rules>

Khai báo các **phụ thuộc chặn (blocking edges)** cho từng ticket: các ticket bắt buộc phải hoàn thành trước khi ticket này có thể bắt đầu. Ticket nào không bị phụ thuộc có thể bắt tay vào làm ngay.

**Ngoại lệ duy nhất đối với lát cắt dọc là Tái cấu trúc diện rộng (Wide refactors):** Tái cấu trúc diện rộng là những thay đổi mang tính cơ học (như đổi tên cột, đổi kiểu dữ liệu dùng chung) có **bán kính ảnh hưởng (blast radius)** lan ra toàn bộ codebase; một sửa đổi nhỏ có thể làm gãy hàng nghìn vị trí gọi hàm (call sites) cùng lúc, khiến không lát cắt dọc nào có thể pass test (xanh) ngay được. Đừng ép nó thành đạn vạch đường, mà hãy triển khai theo mô hình **Mở rộng – Thu hẹp (Expand–Contract)**:
1. *Mở rộng (Expand):* Thêm hàm/cấu trúc mới song song với cái cũ để không làm gãy hệ thống.
2. *Chuyển đổi (Migrate):* Chuyển dần các vị trí gọi hàm (call sites) theo từng đợt chia theo bán kính ảnh hưởng (theo từng package, từng thư mục); mỗi đợt là một ticket bị chặn bởi bước mở rộng, đảm bảo CI luôn xanh qua từng đợt vì cấu trúc cũ vẫn tồn tại.
3. *Thu hẹp (Contract):* Xóa bỏ cấu trúc cũ sau khi không còn ai gọi đến nữa; ticket này bị chặn bởi tất cả các đợt chuyển đổi trước đó. Nếu ngay cả từng đợt cũng không thể xanh riêng lẻ, hãy gom chúng trên một nhánh tích hợp chung (integration branch) để cùng chặn ticket tích hợp & kiểm chứng cuối cùng.

### 4. Trao đổi và lấy phản hồi từ người dùng (Quiz the user)

Trình bày danh sách phân rã đề xuất dưới dạng danh sách đánh số. Với mỗi ticket, hiển thị:

- **Tiêu đề (Title)**: Tên mô tả ngắn gọn, rõ ràng.
- **Bị chặn bởi (Blocked by)**: Những ticket nào bắt buộc phải xong trước (nếu có).
- **Giá trị mang lại (What it delivers)**: Hành vi người dùng đầu-cuối (end-to-end) mà ticket này hiện thực hóa.

Hỏi người dùng:

- Độ chi tiết (granularity) của các ticket đã vừa vặn chưa (quá to hay quá nhỏ)?
- Các mối quan hệ phụ thuộc (blocking edges) đã chính xác chưa: mỗi ticket có bị phụ thuộc oan vào ticket không thực sự chặn nó không?
- Có ticket nào cần gộp lại hoặc tách nhỏ hơn nữa không?

Lặp lại trao đổi cho đến khi người dùng phê duyệt phương án phân rã.

### 5. Xuất bản ticket vào Tracker (Publish tickets)

Xuất bản các ticket đã được phê duyệt tùy theo cấu hình từ `/setup-matt-pocock-skills`:

- **File cục bộ (Local markdown)** → Ghi mỗi ticket thành một file riêng biệt tại `.scratch/<feature-slug>/issues/<NN>-<slug>.md`, đánh số từ `01` theo thứ tự phụ thuộc (ticket chặn đứng trước). Phần "Blocked by" ghi số/tiêu đề ticket phụ thuộc. Sử dụng mẫu bên dưới: mỗi ticket một file, TUYỆT ĐỐI KHÔNG gộp chung thành một file duy nhất.
- **Hệ thống theo dõi thực tế (GitHub, Linear, …)** → Tạo mỗi ticket thành một issue theo thứ tự phụ thuộc. Dùng tính năng native blocking/sub-issue của nền tảng; nếu không có thì ghi danh sách issue chặn vào mục "Blocked by". Gán nhãn `ready-for-agent`.

Thực hiện công việc theo **đường biên (frontier)**: nhặt bất kỳ ticket nào mà tất cả các ticket chặn nó đã hoàn thành. Đối với chuỗi tuần tự, làm từ trên xuống dưới.

KHÔNG đóng hoặc sửa đổi issue cha.

<local-ticket-template>

# <NN>: <Tiêu đề ticket>

**Nội dung cần xây dựng (What to build):** Hành vi đầu-cuối hoàn chỉnh mà ticket này cung cấp từ góc nhìn người dùng, không phải danh sách triển khai từng tầng kỹ thuật.

**Bị chặn bởi (Blocked by):** Số hiệu/tiêu đề của các ticket chặn ticket này, hoặc "Không có (Có thể bắt đầu ngay)".

**Trạng thái (Status):** ready-for-agent

- [ ] Tiêu chí chấp thuận 1 (Acceptance criterion 1)
- [ ] Tiêu chí chấp thuận 2 (Acceptance criterion 2)

</local-ticket-template>

<issue-template>

## Issue cha (Parent)

Tham chiếu đến issue cha trên tracker (nếu bắt nguồn từ một issue có sẵn, nếu không hãy bỏ mục này).

## Nội dung cần xây dựng (What to build)

Hành vi đầu-cuối hoàn chỉnh mà ticket này cung cấp từ góc nhìn người dùng, không phải danh sách triển khai từng tầng kỹ thuật.

## Tiêu chí chấp thuận (Acceptance criteria)

- [ ] Tiêu chí 1
- [ ] Tiêu chí 2

## Bị chặn bởi (Blocked by)

- Danh sách tham chiếu đến từng ticket chặn, hoặc "Không có (Có thể bắt đầu ngay)".

</issue-template>

Ở cả hai định dạng, tránh đưa vào đường dẫn file cụ thể hoặc đoạn code chi tiết: chúng rất nhanh bị lỗi thời. Ngoại lệ: nếu bản prototype tạo ra một đoạn code thể hiện quyết định chính xác hơn lời văn (như state machine, reducer, schema, kiểu dữ liệu), hãy đính kèm phần code quan trọng đó và ghi chú ngắn gọn nguồn từ prototype.
