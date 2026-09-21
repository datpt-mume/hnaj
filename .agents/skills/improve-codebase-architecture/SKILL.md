---
name: improve-codebase-architecture
description: "Quét codebase để tìm kiếm các cơ hội làm sâu module (deepening opportunities), trình bày dưới dạng một báo cáo HTML trực quan, sau đó phỏng vấn chất vấn sâu (grill) về phương án người dùng chọn."
disable-model-invocation: true
---

# Cải Thiện Kiến Trúc Codebase (Improve Codebase Architecture)

Phát hiện các điểm ma sát trong kiến trúc và đề xuất **các cơ hội làm sâu module (deepening opportunities)**: những đợt tái cấu trúc biến các module nông cồng kềnh thành các module sâu tinh gọn. Mục tiêu là nâng cao khả năng kiểm thử và giúp AI dễ dàng điều hướng codebase.

Lệnh này được định hướng bởi mô hình nghiệp vụ của dự án và xây dựng trên bộ từ vựng thiết kế chung:

- Tham chiếu kỹ năng `codebase-design` để nắm bộ từ vựng kiến trúc (**module**, **interface**, **depth**, **seam**, **adapter**, **leverage**, **locality**) và các nguyên lý của nó. Sử dụng chính xác các thuật ngữ này trong mọi gợi ý.
- Ngôn ngữ nghiệp vụ trong `CONTEXT.md` cung cấp tên gọi cho các khớp nối tốt; các tài liệu ADR trong `docs/adr/` ghi lại các quyết định mà lệnh này không nên đảo ngược tùy tiện.

## Quy trình thực hiện

### 1. Khám phá (Explore)

**Khoanh vùng trước khi quét (YAGNI):** Việc làm sâu một module chỉ thực sự đem lại giá trị khi nó giúp các thay đổi trong tương lai trở nên dễ dàng hơn. Do đó, hãy ưu tiên các phần của codebase gần đây thường xuyên thay đổi:

- Nếu người dùng đã chỉ định một hướng cụ thể (một module, một hệ thống con, hoặc một điểm nghẽn khó chịu), hãy tập trung vào đó và bỏ qua bước suy đoán.
- Nếu không, hãy duyệt lại lịch sử commit gần đây (`git log --oneline`) để tìm ra các "điểm nóng" (hot spots) của codebase — những file và khu vực liên tục bị chỉnh sửa — để hướng sự chú ý vào đó trước tiên.

Đọc qua thuật ngữ nghiệp vụ (`CONTEXT.md`) và bất kỳ ADR nào liên quan trước.

Khởi tạo một sub-agent để rà soát codebase và ghi nhận những nơi xuất hiện ma sát kiến trúc:
- Nơi nào để hiểu một khái niệm nghiệp vụ mà phải nhảy qua nhảy lại giữa quá nhiều module nhỏ vụn vặt?
- Nơi nào module bị **nông (shallow)**, với một interface phức tạp gần bằng chính phần code triển khai bên trong?
- Nơi nào các hàm thuần túy (pure functions) bị bóc tách ra chỉ để phục vụ viết test, nhưng lỗi thực tế lại ẩn nấp ở cách chúng được gọi (thiếu **tính cục bộ - locality**)?
- Nơi nào các module liên kết quá chặt (tightly-coupled) làm rò rỉ chi tiết qua khớp nối (seam)?
- Phần nào của codebase chưa có test, hoặc rất khó test qua interface hiện tại?

Áp dụng **phép thử xóa bỏ (deletion test)** cho bất kỳ thứ gì bạn nghi ngờ là module nông: Nếu xóa nó đi thì độ phức tạp có gom lại một chỗ không? Nếu có, đó chính là ứng viên bạn cần tìm.

### 2. Trình bày ứng viên dưới dạng Báo cáo HTML (HTML Report)

Tạo một file HTML độc lập vào thư mục tạm của hệ điều hành (`$TMPDIR` hoặc `/tmp`) với định dạng `<tmpdir>/architecture-review-<timestamp>.html` để mỗi lần chạy có một file mới, không làm rác repo. Mở file đó cho người dùng (`xdg-open` trên Linux, `open` trên macOS) và thông báo đường dẫn tuyệt đối cho họ.

Báo cáo sử dụng **Tailwind CSS qua CDN** và **Mermaid qua CDN** để vẽ biểu đồ luồng/cấu trúc trực quan, kết hợp hình vẽ minh họa CSS/SVG tùy chỉnh. Mỗi ứng viên phải có **hình ảnh minh họa so sánh Trước / Sau (Before / After)**.

Mỗi thẻ ứng viên (card) hiển thị:
- **Files**: Các file / module liên quan.
- **Problem (Vấn đề)**: Vì sao kiến trúc hiện tại gây ra ma sát.
- **Solution (Giải pháp)**: Mô tả rõ ràng những gì sẽ thay đổi.
- **Benefits (Lợi ích)**: Giải thích theo góc độ tính cục bộ (locality), đòn bẩy (leverage), và cách các bài test sẽ được cải thiện.
- **Sơ đồ Trước / Sau (Before / After diagram)**: Minh họa sự nông của kiến trúc cũ và sự sâu của kiến trúc mới.
- **Mức độ khuyến nghị (Recommendation strength)**: Đánh dấu nhãn `Strong` (Rất nên làm), `Worth exploring` (Đáng khám phá), hoặc `Speculative` (Phỏng đoán).

Kết thúc báo cáo bằng phần **Khuyến nghị hàng đầu (Top recommendation)**: Ứng viên nào nên xử lý đầu tiên và lý do.

Xem [HTML-REPORT.md](HTML-REPORT.md) để lấy khung HTML mẫu và hướng dẫn vẽ sơ đồ.

*CHƯA vội đề xuất interface ở bước này. Sau khi tạo file, hãy hỏi người dùng: "Bạn muốn đi sâu vào phương án nào trong số này?"*

### 3. Vòng lặp phỏng vấn chất vấn (Grilling loop)

Sau khi người dùng chọn một ứng viên, tiến hành phỏng vấn sâu với họ về cây quyết định: các ràng buộc, phụ thuộc, hình dạng của module sau khi làm sâu, những gì nằm sau seam, bài test nào sẽ tồn tại.

- **Đặt tên module theo khái niệm chưa có trong `CONTEXT.md`?** Bổ sung thuật ngữ vào `CONTEXT.md`.
- **Người dùng từ chối ứng viên vì một lý do quan trọng mang tính nền tảng?** Đề xuất ghi lại thành một tài liệu quyết định kiến trúc (ADR) để các lần rà soát sau không gợi ý lại điều này nữa.
- **Muốn khám phá các phương án giao diện thay thế cho module sâu?** Sử dụng kỹ năng `codebase-design` và quy trình song song "Design It Twice".
