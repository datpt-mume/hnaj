# Bối cảnh sản phẩm

> **Trạng thái:** Đã hoàn thành **Bước 1 (Nền tảng kỹ thuật)**: khởi tạo `backend/` (Spring Boot 4.1.2, Java 21, Maven Wrapper) với endpoint `GET /api/health` và `frontend/` (Next.js 16.4.3, TypeScript strict) hiển thị trạng thái kết nối tiếng Việt kèm bộ kiểm thử tự động (`npm run typecheck`, `npm test`). Các chức năng nghiệp vụ từ Bước 2 trở đi (Địa điểm, Đánh giá, Đề xuất, Nội dung cộng đồng) đều **dự kiến, chưa triển khai**.

## Hướng phát triển đã chốt

- **Backend:** Java 21 + Spring Boot (`backend/`), dùng Maven Wrapper, cung cấp API cho website và có thể tái sử dụng cho ứng dụng di động sau này.
- **Frontend:** Next.js App Router + TypeScript strict (`frontend/`), giao diện tiếng Việt.
- **Cách thực hiện:** Dự án cá nhân theo hướng vừa học Spring Boot vừa xây sản phẩm, triển khai từng bước nhỏ có thể chạy và kiểm thử; không xây toàn bộ mạng xã hội ngay từ đầu. Kinh nghiệm Laravel trước đây là nền tảng tham khảo, không phải công nghệ backend của dự án này.
- **Chưa chốt:** Cơ sở dữ liệu, cách xác thực, hạ tầng triển khai và thiết kế API nghiệp vụ cụ thể.

## Lộ trình từng bước dự kiến

1. **Nền tảng (Đã triển khai):** Khởi tạo backend Spring Boot (`GET /api/health`) và frontend Next.js; gọi API kiểm tra kết nối phía server, cấu hình chạy cục bộ và kiểm thử tự động ở cả hai đầu.
2. **Địa điểm (Dự kiến tiếp theo):** Thiết kế dữ liệu, nhập dữ liệu Địa điểm ban đầu từ file SQL; xây API và trang tra cứu/xem chi tiết Địa điểm. Đây là lát cắt nghiệp vụ đầu tiên để thực hành lưu trữ dữ liệu và API.
3. **Đánh giá:** Tài khoản, đăng nhập và quyền truy cập cần thiết; tạo/sửa/xóa Đánh giá, giới hạn một Đánh giá cho mỗi cặp người dùng–Địa điểm; hiển thị Đánh giá trên trang Địa điểm.
4. **Đề xuất Địa điểm:** Lọc theo các điều kiện đã chọn, xếp hạng, giữ đợt tối đa 50 Địa điểm, Roll lại và mở rộng bán kính theo quy tắc trong tài liệu này. Chốt các thông số còn TBD trước khi triển khai bước này.
5. **Nội dung cộng đồng:** Bài viết, Bài viết lịch trình, feed, tìm kiếm Bài viết, thích/bình luận, theo dõi; sau đó bổ sung luồng đề xuất Địa điểm mới và dashboard quản trị.

Mỗi bước chỉ được xem là hoàn thành khi hành vi tương ứng chạy được và có kiểm thử; cập nhật trạng thái thực tế trong tài liệu, không coi lộ trình là chức năng đã có. Thứ tự chi tiết trong từng bước có thể điều chỉnh theo quá trình học.

## Mục tiêu và phạm vi

Xây dựng mạng xã hội trên web để mọi người chia sẻ trải nghiệm ăn uống, vui chơi, giải trí và du lịch tại các địa điểm. Người dùng có thể đọc bài viết, xem thông tin địa điểm và đánh giá của cộng đồng, hoặc nhận đề xuất nơi nên thử khi chưa biết đi đâu. Backend API dự kiến được tái sử dụng cho ứng dụng di động sau này.

Sản phẩm hướng tới người dùng Việt Nam, giao diện tiếng Việt; dữ liệu khởi đầu ở Hà Nội, có thể mở rộng địa bàn sau. Backend dùng Java Spring Boot, frontend dùng Next.js; các lựa chọn kỹ thuật chi tiết khác chưa chốt.

## Thuật ngữ nghiệp vụ

| Thuật ngữ | Ý nghĩa |
| --- | --- |
| **Địa điểm (Place)** | Nơi ăn uống, vui chơi, giải trí, du lịch... có trang thông tin, khu vực, loại hình, dữ liệu ngân sách và các phong cách/thuộc tính như “sang trọng”, “cho phép thú cưng”. Có thể được gắn trong Bài viết và nhận Đánh giá. |
| **Bài viết (Post)** | Nội dung trên feed: status, ảnh và câu chuyện trải nghiệm. Không chấm sao; độc lập với Đánh giá. Có thể gắn 0–n Địa điểm để hỗ trợ tìm kiếm và check-in. |
| **Bài viết lịch trình** | Cũng là Bài viết, không phải thực thể nội dung tách biệt. Người viết có thể đánh dấu là lịch trình và sắp thứ tự các Địa điểm ghé thăm, ví dụ food tour nhiều điểm. |
| **Đánh giá (Review)** | Nội dung gắn với đúng một Địa điểm, có số sao; không tự động trở thành Bài viết trên feed. Giới hạn độ dài nội dung và số ảnh thấp hơn Bài viết. |
| **Bộ lọc đề xuất** | Điều kiện người dùng tùy chọn (loại hình, khu vực/khoảng cách, ngân sách, phong cách); khi đã chọn thì phải khớp để Địa điểm lọt vào tập ứng viên. |
| **Trọng số đề xuất** | Điểm xếp hạng các Địa điểm đã qua bộ lọc, không phải phép chọn ngẫu nhiên thuần túy. |
| **Roll lại** | Xem ứng viên tiếp theo trong danh sách đề xuất đã xếp hạng; không đồng nghĩa với chạy lại truy vấn khi vẫn còn kết quả trong đợt hiện tại. |

## Vai trò và quyền truy cập dự kiến

- **Khách:** Xem thông tin Địa điểm, Bài viết/feed và Đánh giá công khai; tra cứu và dùng tính năng đề xuất mà không cần đăng nhập.
- **Người dùng:** Đăng ký/đăng nhập bằng email và mật khẩu; được đăng Bài viết, Đánh giá, đề xuất Địa điểm mới, thích/bình luận Bài viết và theo dõi người dùng khác.
- **Quản trị viên:** Duyệt đề xuất Địa điểm; tự thêm Địa điểm trong dashboard quản lý; có thể ẩn hoặc xóa Bài viết và Đánh giá vi phạm.

## Nội dung cộng đồng dự kiến

- **Bài viết:** Linh hoạt như bài mạng xã hội: đăng status và/hoặc ảnh, có thể không gắn Địa điểm hoặc gắn nhiều Địa điểm. Bài viết lịch trình đánh dấu riêng và có thứ tự ghé thăm. Không dùng số sao cho Bài viết, kể cả khi kể trải nghiệm tại một nơi.
- **Đánh giá:** Gắn với trang Địa điểm, có chấm sao; tối đa **một Đánh giá cho mỗi cặp người dùng–Địa điểm**. Người viết được sửa hoặc xóa Đánh giá. Không yêu cầu check-in hay xác minh vị trí thiết bị trước khi Đánh giá. Giới hạn nội dung và ảnh thấp hơn Bài viết (mức cụ thể chưa chốt).
- **Feed và tương tác:** Có feed khám phá toàn hệ thống và feed Bài viết từ người được theo dõi; cả hai sắp theo thời gian đăng mới nhất trước. Hỗ trợ thích, bình luận Bài viết và theo dõi tài khoản. Chưa đặt thuật toán xếp hạng cho feed.
- **Tìm kiếm:** Tìm Bài viết theo nội dung chữ và Địa điểm được gắn; Bài viết lịch trình cũng tìm được. Cho phép tra cứu Địa điểm theo tên để xem thông tin và Đánh giá. Tra cứu Địa điểm là luồng khác với đề xuất nơi nên đi.

## Nguồn dữ liệu Địa điểm

- File nguồn dữ liệu ban đầu là [maps.sql](./maps.sql), một MySQL SQL dump được dùng để import vào hệ thống và làm dữ liệu thực tế cho các địa điểm; không xem đây chỉ là fixture/test data.
- Dump được xuất từ schema `maps`, sử dụng MySQL 8.4.11, mã hóa `utf8mb4` và có các lệnh `SET FOREIGN_KEY_CHECKS` để import độc lập.
- Dump hiện có ba bảng: `place_index` (chỉ mục `place_id` và `created_at`), `places` (thông tin chi tiết địa điểm), và `poi_places` (liên kết loại POI với `place_id`). Mỗi bảng hiện có 100 bản ghi INSERT.
- Bảng `places` là nguồn chính cho trang/API Địa điểm, gồm `record_key`, `place_id`, URL Google Maps, tên, danh mục, địa chỉ, điện thoại, website, tọa độ `latitude`/`longitude`, `data_json` và `updated_at`. `data_json` chứa dữ liệu thu thập mở rộng như ảnh, giờ mở cửa, điểm đánh giá, mã khu vực và văn bản thô; cần parse/validate khi dùng, không giả định mọi key đều có mặt.
- `place_id` là định danh Google Maps và khóa liên kết giữa ba bảng. Khi thiết kế migration/entity/importer, phải giữ khả năng liên kết bằng `place_id`, hỗ trợ Unicode tiếng Việt và không làm mất dữ liệu JSON gốc.
- Dữ liệu hiện tập trung ở Hà Nội và có thể chứa địa điểm ngoài nhóm ăn uống (ví dụ dịch vụ, thú y); không hard-code giả định mọi bản ghi là nhà hàng. Các bộ lọc/phân loại nghiệp vụ cần dựa trên dữ liệu thực tế và có chiến lược xử lý giá trị thiếu.
- Khi import lại, chạy theo thứ tự `place_index` → `places` → `poi_places` nếu có ràng buộc khóa ngoại; chỉ dùng dump này cho môi trường được phép và không sửa trực tiếp dữ liệu nguồn nếu chưa có yêu cầu.


## Luồng đề xuất Địa điểm dự kiến

1. Xin quyền vị trí; **vị trí hiện tại** là lựa chọn mặc định cho khu vực tìm kiếm. Nếu người dùng không cấp được vị trí, cho chọn khu vực thủ công rồi tiếp tục. Các bộ lọc loại hình (ăn uống, giải trí...), ngân sách, khu vực/khoảng cách và phong cách đều tùy chọn, nhưng **mỗi điều kiện đã chọn đều bắt buộc khớp** dữ liệu Địa điểm.
2. Lấy các Địa điểm thỏa bộ lọc, sau đó xếp theo Trọng số đề xuất từ cao xuống thấp. Các tín hiệu dự kiến gồm điểm Đánh giá, độ phổ biến và độ mới; chưa chốt công thức, hệ số hay cách tính từng tín hiệu. Chưa cá nhân hóa theo lịch sử người dùng.
3. Lấy một đợt tối đa **50 Địa điểm**, hiển thị từng Địa điểm theo thứ tự xếp hạng. Mỗi lần Roll lại dùng phần tử tiếp theo của đợt đang giữ, **không truy vấn lại** nếu bộ lọc không đổi và đợt chưa hết. Không hiển thị lại Địa điểm đã xem trong cùng lượt đề xuất.
4. Khi hết đợt, mở rộng bán kính tìm kiếm và lấy đợt mới, chỉ gồm Địa điểm chưa xem. Nếu thay đổi bộ lọc, truy vấn và xếp hạng lại từ đầu theo điều kiện mới. Khi không còn ứng viên phù hợp thì thông báo hết kết quả.

## Chưa quyết định (TBD)

- Công thức và hệ số Trọng số đề xuất; định nghĩa chính xác của “độ phổ biến” và “độ mới”.
- Giới hạn ký tự/số ảnh của Bài viết và Đánh giá (chỉ chốt Đánh giá thấp hơn Bài viết).
- Bán kính ban đầu, bước mở rộng, giới hạn xa nhất và quy tắc xử lý khi không có kết quả trong bán kính; chỉ chốt hướng mở rộng sau mỗi đợt đề xuất.
- Cấu trúc kỹ thuật chi tiết, phiên bản Spring Boot/Next.js, cơ sở dữ liệu, xác thực, thiết kế API và thứ tự chi tiết trong từng bước triển khai.

Khi một quyết định được chốt hoặc một chức năng được triển khai, cập nhật tài liệu này để phân biệt **dự kiến** với **đã triển khai**; đối chiếu hành vi thực tế với mã nguồn và kiểm thử, không suy diễn trạng thái chỉ từ kế hoạch.
