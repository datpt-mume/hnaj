# Tài Liệu Nghiệp Vụ (Domain Docs)

Cách các kỹ năng kỹ thuật tiếp nhận và sử dụng tài liệu nghiệp vụ của dự án này khi khám phá codebase.

## Trước khi khám phá codebase, hãy đọc các tài liệu này:

- **`CONTEXT.md`** tại thư mục gốc của repo, hoặc
- **`CONTEXT-MAP.md`** tại thư mục gốc nếu tồn tại (trong dự án monorepo): nó trỏ tới từng file `CONTEXT.md` cho mỗi ngữ cảnh. Hãy đọc các file liên quan đến chủ đề bạn đang xử lý.
- **`docs/adr/`**: đọc các tài liệu Quyết định Kiến trúc (ADR - Architecture Decision Records) liên quan trực tiếp đến khu vực mã nguồn bạn sắp sửa đổi. Trong repo đa ngữ cảnh, hãy kiểm tra thêm `src/<context>/docs/adr/`.

Nếu bất kỳ file nào trong số này chưa tồn tại, **hãy âm thầm tiếp tục công việc bình thường**. Không phàn nàn về sự thiếu vắng của chúng; không tự ý đề xuất tạo trước khi chưa cần. Kỹ năng `/domain-modeling` (hoặc `/grill-with-docs`) sẽ khởi tạo chúng khi cần (lazy creation) khi các thuật ngữ hoặc quyết định kiến trúc thực sự được giải quyết.

## Cấu trúc thư mục (File structure)

Dự án đơn ngữ cảnh (Single-context - áp dụng cho hầu hết các repo):

```
/
├── CONTEXT.md
├── docs/adr/
│   ├── 0001-event-sourced-orders.md
│   └── 0002-postgres-for-write-model.md
└── src/
```

Dự án đa ngữ cảnh (Multi-context - khi có file `CONTEXT-MAP.md` tại thư mục gốc):

```
/
├── CONTEXT-MAP.md
├── docs/adr/                          ← Quyết định cho toàn hệ thống
└── src/
    ├── ordering/
    │   ├── CONTEXT.md
    │   └── docs/adr/                  ← Quyết định riêng cho ngữ cảnh ordering
    └── billing/
        ├── CONTEXT.md
        └── docs/adr/
```

## Luôn sử dụng từ vựng trong Bảng thuật ngữ nghiệp vụ

Khi kết quả đầu ra của bạn nhắc đến một khái niệm nghiệp vụ (trong tiêu đề issue, đề xuất tái cấu trúc, giả thuyết lỗi, hoặc tên bài test), **hãy sử dụng chính xác thuật ngữ đã được định nghĩa trong `CONTEXT.md`**. Đừng tự ý chuyển sang các từ đồng nghĩa mà bảng thuật ngữ đã cố ý tránh.

Nếu khái niệm bạn cần chưa xuất hiện trong bảng thuật ngữ, đó là dấu hiệu: hoặc bạn đang tự bịa ra từ ngữ mà dự án không dùng (hãy cân nhắc lại), hoặc dự án đang thực sự có một khoảng trống thuật ngữ cần bổ sung.

## Đánh dấu xung đột với ADR

Nếu đề xuất hoặc kết quả của bạn mâu thuẫn với một quyết định kiến trúc (ADR) đã có từ trước, hãy nêu rõ ràng thay vì âm thầm ghi đè:

> _"Mâu thuẫn với ADR-0007 (Event-sourced orders), nhưng đáng để xem xét lại vì..."_
