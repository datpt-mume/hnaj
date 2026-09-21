---
name: code-review
description: "Đánh giá các thay đổi kể từ một điểm mốc (commit, branch, tag, hoặc merge-base) theo hai trục độc lập: Tiêu chuẩn (Standards - mã nguồn có tuân thủ quy ước dự án không?) và Đặc tả (Spec - mã nguồn có đáp ứng đúng yêu cầu của issue/spec không?). Chạy song song qua 2 sub-agent độc lập và báo cáo cạnh nhau."
---

# Đánh Giá Mã Nguồn (Code Review)

Đánh giá theo hai trục độc lập đối với sự khác biệt (diff) giữa `HEAD` và một điểm mốc cố định do người dùng cung cấp:

- **Tiêu chuẩn (Standards)**: Mã nguồn có tuân thủ các tiêu chuẩn lập trình được tài liệu hóa của dự án và các nguyên lý clean code không?
- **Đặc tả (Spec)**: Mã nguồn có hiện thực hóa trung thực và chính xác những gì issue / spec ban đầu yêu cầu không?

Cả hai trục đánh giá đều chạy dưới dạng **các sub-agent song song** để ngữ cảnh không bị nhiễu lẫn nhau, sau đó kỹ năng này sẽ tổng hợp kết quả của cả hai.

Trình theo dõi công việc (issue tracker) phải được cấu hình sẵn. Nếu thiếu file `docs/agents/issue-tracker.md`, hãy hướng dẫn người dùng chạy `/setup-matt-pocock-skills`.

## Quy trình thực hiện

### 1. Xác định điểm mốc cố định (Pin the fixed point)

Bất kỳ mốc nào người dùng cung cấp (mã commit SHA, tên branch, tag, `main`, `HEAD~5`...). Nếu người dùng chưa chỉ định, hãy hỏi họ.

Chạy lệnh lấy diff một lần duy nhất: `git diff <fixed-point>...HEAD` (dùng dấu 3 chấm để so sánh với merge-base). Đồng thời lấy danh sách commit qua: `git log <fixed-point>..HEAD --oneline`.

Kiểm tra xem mốc tham chiếu có hợp lệ không (`git rev-parse <fixed-point>`) và diff có dữ liệu không trước khi chạy tiếp.

### 2. Xác định nguồn đặc tả (Identify the spec source)

Tìm kiếm đặc tả gốc theo thứ tự ưu tiên sau:

1. Mã tham chiếu issue trong commit message (`#123`, `Closes #45`,...), truy xuất qua quy trình trong `docs/agents/issue-tracker.md`.
2. Đường dẫn người dùng truyền vào như một đối số.
3. File spec nằm trong `docs/`, `specs/`, hoặc `.scratch/` khớp với tên branch hoặc tính năng.
4. Nếu không tìm thấy, hãy hỏi người dùng spec nằm ở đâu. Nếu người dùng xác nhận không có spec, sub-agent **Spec** sẽ bỏ qua và báo cáo "không có đặc tả để đối chiếu".

### 3. Xác định nguồn tiêu chuẩn (Identify the standards sources)

Bất kỳ tài liệu nào trong repo quy định tiêu chuẩn viết mã, ví dụ `CODING_STANDARDS.md`, `CONTRIBUTING.md` hoặc các skill liên quan.

Bên cạnh tiêu chuẩn riêng của repo, trục Standards luôn áp dụng bộ tiêu chí **dấu hiệu mã xấu cơ sở (smell baseline)** theo Martin Fowler (*Refactoring*, Chương 3):

- **Tiêu chuẩn của Repo luôn ưu tiên số 1:** Nếu repo cho phép một cách viết cụ thể mà baseline coi là mùi mã xấu, hãy tuân theo quy định của repo.
- **Luôn là đánh giá mang tính phán đoán:** Mỗi smell là một gợi ý phán đoán (heuristic, ví dụ: "có thể là Feature Envy"), không phải vi phạm cứng. Bỏ qua bất kỳ điều gì mà công cụ linter/typechecker đã tự động kiểm tra.

Danh sách 12 Code Smell cơ sở cần quét:

- **Mysterious Name (Tên khó hiểu):** Hàm, biến hoặc kiểu dữ liệu có tên không thể hiện rõ nó làm gì hoặc chứa gì $\rightarrow$ Đổi tên; nếu không tìm được tên chân thực thì thiết kế đang có vấn đề.
- **Duplicated Code (Mã trùng lặp):** Cùng một cấu trúc logic xuất hiện ở nhiều nơi $\rightarrow$ Trích xuất thành hàm dùng chung và gọi từ cả hai nơi.
- **Feature Envy (Thèm muốn tính năng):** Một phương thức chõ mũi vào dữ liệu của đối tượng khác nhiều hơn dữ liệu của chính nó $\rightarrow$ Chuyển phương thức đó về đối tượng chứa dữ liệu.
- **Data Clumps (Cụm dữ liệu):** Cùng một nhóm tham số/trường dữ liệu luôn đi cùng nhau $\rightarrow$ Gom chúng thành một kiểu/đối tượng riêng.
- **Primitive Obsession (Ám ảnh kiểu nguyên thủy):** Lạm dụng string, number thô sơ để biểu diễn một khái niệm nghiệp vụ xứng đáng có kiểu dữ liệu riêng $\rightarrow$ Tạo type/class riêng (ví dụ: `EmailAddress`, `Money`).
- **Repeated Switches (Switch/If lặp lại):** Các khối switch/if-else tương tự nhau lặp lại trên cùng một kiểu dữ liệu ở nhiều file $\rightarrow$ Thay thế bằng đa hình (polymorphism) hoặc một map dùng chung.
- **Shotgun Surgery (Phẫu thuật đạn ghém):** Một thay đổi logic buộc phải sửa rải rác ở quá nhiều file $\rightarrow$ Gom những thứ cùng thay đổi về một module duy nhất.
- **Divergent Change (Thay đổi phân kỳ):** Một file/module liên tục bị sửa đổi vì nhiều lý do hoàn toàn không liên quan $\rightarrow$ Tách nhỏ để mỗi module chỉ thay đổi vì một lý do duy nhất.
- **Speculative Generality (Trừu tượng hóa suy đoán):** Thêm các lớp trừu tượng, hook hoặc tham số dự phòng cho những tình huống mà spec không hề yêu cầu $\rightarrow$ Xóa bỏ, giữ code đơn giản và thực tế.
- **Message Chains (Chuỗi gọi hàm dài dòng):** Chuỗi truy cập dài lê thê `a.b().c().d()` khiến caller phụ thuộc vào cấu trúc bên trong $\rightarrow$ Ẩn chuỗi đó đằng sau một phương thức trên đối tượng đầu tiên.
- **Middle Man (Người trung gian vô dụng):** Một class/hàm hầu như chỉ làm mỗi việc ủy quyền chuyển tiếp cho đối tượng khác $\rightarrow$ Cắt bỏ, gọi trực tiếp đối tượng thực tế.
- **Refused Bequest (Từ chối thừa kế):** Lớp con kế thừa nhưng bỏ qua hoặc ghi đè làm vô hiệu hầu hết những gì lớp cha cung cấp $\rightarrow$ Bỏ kế thừa, chuyển sang dùng composition (kết hợp).

### 4. Khởi tạo 2 Sub-Agent chạy song song

**Prompt cho Sub-agent Standards:**
- Lệnh git diff đầy đủ và danh sách commit.
- Danh sách file tiêu chuẩn của repo + toàn bộ 12 code smells cơ sở ở trên.
- Yêu cầu: "Báo cáo: (a) Mọi vị trí trong diff vi phạm tiêu chuẩn của repo (nêu rõ file + điều luật); (b) Bất kỳ code smell cơ sở nào phát hiện được: nêu tên và trích dẫn đoạn code liên quan. Phân biệt rõ vi phạm cứng và phán đoán gợi ý. Xuất dưới 400 từ."

**Prompt cho Sub-agent Spec:**
- Lệnh git diff đầy đủ và danh sách commit.
- Nội dung file spec.
- Yêu cầu: "Báo cáo: (a) Yêu cầu nào trong spec bị thiếu hoặc chưa hoàn thiện; (b) Hành vi nào có trong diff nhưng spec không hề yêu cầu (scope creep); (c) Yêu cầu nào đã làm nhưng làm sai so với spec. Trích dẫn dòng spec tương ứng cho mỗi phát hiện. Xuất dưới 400 từ."

### 5. Tổng hợp báo cáo (Aggregate)

Trình bày kết quả dưới 2 mục lớn `## Standards` và `## Spec` độc lập. **KHÔNG gộp hay xếp hạng lại các phát hiện giữa 2 bên**, vì hai trục này đánh giá hai góc độ hoàn toàn khác nhau:

- Code tuân thủ mọi chuẩn mực sạch sẽ nhưng chạy sai yêu cầu $\rightarrow$ **Standards đạt, Spec trượt.**
- Code giải quyết đúng 100% bài toán nhưng code cực kỳ cẩu thả $\rightarrow$ **Spec đạt, Standards trượt.**

Kết luận bằng 1 dòng tóm tắt: Tổng số phát hiện của từng trục và vấn đề nghiêm trọng nhất của mỗi bên.
