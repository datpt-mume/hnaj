# PRD — HNAJ: Nền tảng khám phá địa điểm ngẫu nhiên

- **Trạng thái:** Draft — đã chốt nghiệp vụ nền và các quyết định QA vòng 3, còn mở một số chính sách mở rộng
- **Phiên bản:** 0.3
- **Ngày soạn:** 2026-07-24
- **Cập nhật:** 2026-07-29 (authentication)
- **Ngôn ngữ:** Tiếng Việt
- **Phạm vi tài liệu:** Mô tả sản phẩm, nghiệp vụ, dữ liệu và yêu cầu chức năng ở mức định hướng. Tài liệu này chưa phải thiết kế database, API specification hoặc technical design.

> **Lưu ý:** Repository hiện mới là boilerplate Laravel/React/Docker và chưa có domain logic. Các nội dung trong tài liệu này được tổng hợp từ trao đổi với người dùng. Mục có nhãn **TBD / Cần xác nhận** chưa được xem là quyết định cuối cùng và không nên dùng để dựng migration trước khi được chốt.

---

## 1. Tóm tắt sản phẩm

HNAJ là nền tảng giúp người dùng tìm một địa điểm phù hợp với nhu cầu tức thời bằng cách chọn các tiêu chí mong muốn, sau đó hệ thống đề xuất ngẫu nhiên một địa điểm đáp ứng các tiêu chí đó.

Các nhóm địa điểm dự kiến gồm nhưng không giới hạn:

- Ăn uống.
- Cafe.
- Vui chơi.
- Các địa điểm trải nghiệm hoặc giải trí khác.

Khác với công cụ tìm kiếm chỉ trả về danh sách kết quả, trọng tâm của HNAJ là giảm thời gian phân vân bằng cách đưa ra một lựa chọn cụ thể. Người dùng có thể roll lại nếu không phù hợp. Khi người dùng chọn **“Đi tới đó”**, hệ thống mở Google Maps để chỉ đường và ghi nhận đó là một lần người dùng đi tới địa điểm được đề xuất.

Về lâu dài, dữ liệu bookmark, lịch sử đi tới và đánh giá có thể được dùng để cải thiện chất lượng đề xuất, nhưng phiên bản đầu tiên vẫn ưu tiên cơ chế random dựa trên bộ lọc.

## 2. Vấn đề cần giải quyết

Người dùng thường:

- Không biết nên đi đâu dù có nhu cầu ăn uống hoặc vui chơi.
- Mất nhiều thời gian xem quá nhiều lựa chọn.
- Khó lọc địa điểm theo bối cảnh cụ thể như khu vực, ngân sách, phong cách hoặc thời gian mở cửa.
- Muốn có một lựa chọn nhanh thay vì phải tự quyết định từ một danh sách dài.
- Muốn lưu lại địa điểm đã thích và tra cứu những nơi đã từng đi.

HNAJ giải quyết vấn đề bằng một trải nghiệm khám phá ngắn gọn:

1. Chọn nhu cầu và bộ lọc.
2. Nhận một địa điểm phù hợp được chọn ngẫu nhiên.
3. Chọn đi tới, lưu lại, đánh giá hoặc roll lại.

## 3. Mục tiêu sản phẩm

### 3.1. Mục tiêu chính

- Cho phép người dùng nhận được một đề xuất địa điểm phù hợp với tiêu chí đã chọn.
- Tạo trải nghiệm random rõ ràng, nhanh và dễ sử dụng.
- Ghi nhận lượt người dùng chọn đi tới địa điểm để xây dựng lịch sử thực tế.
- Cho phép người dùng đã đăng nhập lưu bookmark riêng tư.
- Cho phép người dùng đánh giá và phản hồi sau khi đã đi tới địa điểm.
- Xây dựng dữ liệu địa điểm có cấu trúc, có category, khu vực, mức giá, giờ mở cửa và tags.
- Cho phép quản lý địa điểm cập nhật thông tin nơi mình phụ trách.
- Cho phép Admin kiểm soát chất lượng dữ liệu và các yêu cầu từ cộng đồng.
- Hiển thị các địa điểm nổi bật/hot và các địa điểm được duyệt quảng bá.

### 3.2. Không phải mục tiêu đã chốt

Các nội dung sau chưa được xem là mục tiêu bắt buộc cho phiên bản đầu tiên nếu chưa có quyết định bổ sung:

- Thanh toán trực tuyến hoặc đặt bàn/đặt vé.
- Điều hướng bản đồ nội bộ.
- Xác minh GPS để chứng minh người dùng thực sự có mặt tại địa điểm.
- Mạng xã hội đầy đủ với follow, feed cá nhân hoặc chat.
- Thuật toán recommendation cá nhân hóa phức tạp.
- Hệ thống thanh toán quảng cáo tự động.

## 4. Đối tượng sử dụng và vai trò

### 4.0. Authentication đã chốt

- User đăng ký bằng `username`, họ tên đầy đủ, email và password; chỉ hoàn tất đăng ký sau khi xác thực email.
- User đăng nhập bằng `username + password` hoặc Google OAuth. Google account mới tự sinh username dạng `{local-part}_{mã-ngẫu-nhiên}` để tránh trùng lặp và nhận role `user`.
- Admin đăng nhập bằng endpoint riêng với `username + password`; credential được tạo thủ công qua Tinker, không seed trong source code. Hành động bootstrap admin là one-time create-only: chỉ chạy một lần để tạo admin hệ thống đầu tiên, từ chối mọi lần chạy sau và không cập nhật tài khoản hiện có.
- Role hệ thống giữ nguyên `user`, `sub_admin`, `admin`. Backend kiểm tra role từ database cho từng khu vực và kiểm tra lại role trước khi phát Google token; endpoint user không thay thế endpoint admin.
- API dùng Sanctum bearer token. Google callback chỉ redirect exchange code một lần, không đưa bearer token vào URL. Callback Google không hợp lệ redirect về frontend với lỗi chung thay vì trả JSON cho trình duyệt.
- Chi tiết contract nằm tại [`docs/api-auth.md`](api-auth.md).

### 4.1. Khách chưa đăng nhập

Có thể:

- Truy cập trang chủ.
- Chọn bộ lọc khám phá.
- Nhận đề xuất địa điểm.
- Xem thông tin địa điểm công khai.
- Bấm “Đi tới đó” để mở Google Maps.
- Xem các địa điểm hot theo quyền truy cập công khai.

Bắt buộc đăng nhập để:

- Bookmark địa điểm.
- Xem bookmark cá nhân.
- Xem lịch sử cá nhân.
- Rating, review và comment.
- Gửi request có gắn với tài khoản.

**Đã chốt:** khách chưa đăng nhập không được rating và không được comment. Mọi nội dung do người dùng tạo đều phải gắn với một tài khoản để bảo đảm truy xuất lịch sử, kiểm soát spam và cho phép moderation.

Lượt “Đi tới đó” của khách chưa đăng nhập vẫn mở Google Maps, không tạo lịch sử cá nhân nhưng được tính vào thống kê địa điểm hot.

### 4.2. User

Là người dùng thông thường đã đăng nhập. Có thể:

- Thực hiện toàn bộ chức năng khám phá.
- Lưu và bỏ lưu địa điểm bằng bookmark riêng tư.
- Xem các địa điểm đã đánh dấu.
- Bấm “Đi tới đó” và xem lịch sử đi tới của bản thân.
- Rating và review địa điểm đã từng bấm “Đi tới đó”.
- Comment không kèm rating.
- Gửi yêu cầu thêm địa điểm chưa có trong hệ thống.
- Gửi yêu cầu đăng ký làm người quản lý một địa điểm.
- Gửi yêu cầu quảng bá địa điểm do mình quản lý.

### 4.3. Sub-admin / Quản lý địa điểm

Là tài khoản được Admin duyệt để quản lý một hoặc nhiều địa điểm cụ thể. Đây là người đứng ra chịu trách nhiệm cho địa điểm, ví dụ quản lý nhà hàng hoặc quản lý địa điểm vui chơi.

Có thể, trong phạm vi địa điểm được cấp quyền:

- Cập nhật giờ mở cửa.
- Cập nhật menu nếu địa điểm có menu.
- Cập nhật ảnh địa điểm.
- Phản hồi/reply các đánh giá của người dùng.
- Phản hồi/reply câu hỏi hoặc comment của người dùng.
- Gửi yêu cầu đưa địa điểm lên vị trí nổi bật/đầu danh sách để quảng bá.

Sub-admin không được:

- Quản lý địa điểm ngoài phạm vi được Admin cấp.
- Tự cấp role cho tài khoản khác.
- Tự duyệt địa điểm, request hoặc nội dung của chính mình.
- Thay đổi dữ liệu hệ thống như category/tag toàn cục nếu không có quyền Admin.

### 4.4. Admin

Là vai trò quản trị toàn hệ thống. Có thể:

- Quản lý tài khoản và vai trò.
- Duyệt hoặc từ chối request thêm địa điểm.
- Duyệt địa điểm do User hoặc người quản lý đề xuất.
- Duyệt việc cấp tài khoản Sub-admin.
- Gắn Sub-admin với địa điểm tương ứng.
- Quản lý place, category, khu vực, mức giá và tags.
- Quản lý category và bộ tag toàn cục độc lập.
- Kiểm duyệt ảnh, menu, review, comment và nội dung phản hồi khi cần.
- Xử lý request quảng bá địa điểm.
- Cấu hình hoặc can thiệp dữ liệu hiển thị địa điểm hot.
- Khóa/mở khóa tài khoản hoặc ẩn dữ liệu vi phạm.

## 5. Phạm vi chức năng

### 5.1. Khám phá và random địa điểm

Người dùng có thể chọn không tiêu chí nào hoặc một hay nhiều tiêu chí. Tất cả bộ lọc đều là tuỳ chọn; người dùng có thể bấm random ngay từ trạng thái mặc định.

Các tiêu chí gồm:

- Category: ăn uống, cafe, vui chơi, v.v. Mỗi place chỉ có một category.
- Khu vực: quận/huyện/thị xã thuộc Hà Nội.
- Khoảng giá bằng VND, được chọn bằng range slider với cận dưới và cận trên.
- Khoảng cách.
- Giờ mở cửa/phù hợp với thời điểm truy vấn.
- Tags mô tả địa điểm.

**Đã chốt về khoảng cách:** hệ thống dùng toạ độ GPS của người dùng nếu người dùng cấp quyền vị trí. Nếu người dùng từ chối hoặc trình duyệt không hỗ trợ, hệ thống chuyển sang lọc theo khu vực đã chọn và không áp dụng tiêu chí khoảng cách. Giao diện phải nói rõ tiêu chí khoảng cách đang không được áp dụng.

**Đã chốt về bán kính mặc định:** khi client gửi tọa độ GPS nhưng không gửi radius_km, hệ thống áp dụng mặc định 5km.

**Đã chốt về giờ mở cửa khi khám phá:** tiêu chí open_now mặc định BẬT (client phải gửi false để tắt). Place chưa có dữ liệu giờ mở cửa (unknown) vẫn được coi là hợp lệ và xuất hiện trong kết quả random khi lọc open_now, để tránh loại nhầm place chỉ vì thiếu dữ liệu.

**Đã chốt về fallback khi loại hết ứng viên:** nếu danh sách place bị bỏ qua trong lượt roll hiện tại (excluded) loại hết mọi ứng viên khớp bộ lọc, hệ thống bỏ qua danh sách loại trừ và random lại từ đầu, thay vì báo không tìm thấy kết quả.

Hệ thống thực hiện:

1. Kiểm tra các tiêu chí đầu vào.
2. Tìm các place đang hoạt động và phù hợp với bộ lọc.
3. Loại các place bị loại trong lượt roll hiện tại.
4. Chọn ngẫu nhiên một place từ tập kết quả còn lại.
5. Hiển thị thẻ/kết quả đề xuất và thông tin đủ để người dùng quyết định.

Nếu không có kết quả:

- Thông báo rõ không tìm thấy địa điểm phù hợp.
- Cho phép người dùng nới lỏng hoặc thay đổi bộ lọc.
- Không tự ý bỏ qua tiêu chí mà không thông báo.

Nếu người dùng chọn **roll lại/chọn lại**:

- Địa điểm vừa được đề xuất bị bỏ qua trong lượt khám phá hiện tại.
- Hệ thống không xem việc roll lại là một lần người dùng đi tới.
- Lịch sử “đã đi” không được tạo chỉ vì place được random ra.

**Đã chốt về trạng thái lượt khám phá:** danh sách place bị bỏ qua không được lưu thành dữ liệu lâu dài. Danh sách này thuộc trạng thái tạm thời của lượt khám phá hiện tại và được frontend gửi kèm khi gọi random.

Một lượt khám phá kết thúc khi:

- Người dùng bấm “Đi tới đó”.
- Người dùng thay đổi bộ lọc.
- Người dùng rời hoặc tải lại màn hình khám phá.

Khi lượt khám phá kết thúc, danh sách bỏ qua được reset.

### 5.2. Chi tiết địa điểm

Trang chi tiết place dự kiến hiển thị:

- Tên địa điểm.
- Category duy nhất.
- Địa chỉ chi tiết và quận/huyện/thị xã.
- Số điện thoại, website URL và Google Maps URL nếu có.
- Tọa độ hoặc dữ liệu cần thiết để mở Google Maps.
- Khoảng giá tham khảo bằng VND, gồm `min_price` và `max_price`. Với pipeline CSV, AI nhận chuỗi giá nguồn và trả trực tiếp hai số nguyên VND đã chuẩn hóa; application không tự suy diễn hệ số nghìn bằng parser cục bộ.
- Khoảng cách tương đối nếu có vị trí người dùng.
- Tags hợp lệ theo category.
- Giờ mở cửa.
- Bộ ảnh của place; thumbnail dùng cho card random, các ảnh còn lại hiển thị ở detail.
- Review và comment của User HNAJ.
- Thông tin địa điểm đang được quản lý bởi Sub-admin nếu phù hợp.
- Nút Bookmark.
- Nút “Đi tới đó”.

Chỉ place có `status = active` được hiển thị trong kết quả công khai. Place `hidden` không xuất hiện nhưng dữ liệu liên quan có thể được giữ lại.

### 5.3. Bookmark riêng tư

- Chỉ User đã đăng nhập mới được bookmark.
- Một User không được tạo nhiều bookmark trùng cho cùng một place.
- Bookmark chỉ hiển thị cho chính User đó.
- User có thể bookmark/bỏ bookmark từ card đề xuất hoặc trang chi tiết.
- Place bị ẩn hoặc xóa mềm sẽ tự động được ẩn khỏi danh sách bookmark.
- Bản ghi bookmark không bị xóa; nếu place được khôi phục, bookmark có thể hiển thị lại.

### 5.4. Ghi nhận “Đi tới đó” và Google Maps

Khi người dùng bấm **“Đi tới đó”**:

1. Hệ thống ghi nhận một lượt người dùng chọn đi tới place.
2. Với User đã đăng nhập, lượt này được dùng cho lịch sử cá nhân và thống kê độ hot.
3. Với khách chưa đăng nhập, lượt này chỉ dùng cho thống kê độ hot, không tạo lịch sử cá nhân.
4. Hệ thống mở Google Maps chỉ đường tới địa điểm.
5. Bản ghi nên lưu User nếu có, place, ngày/thời điểm và thông tin ngữ cảnh cần thiết cho thống kê.

Quy tắc nghiệp vụ đã xác nhận:

- Chỉ click “Đi tới đó” mới được coi là một lượt đi tới.
- Roll lại hoặc chọn lại không tạo lượt đi tới.
- Hệ thống chưa xác minh người dùng thực sự có mặt tại place; “đã tới” trong PRD hiện được hiểu là “đã bấm nút Đi tới đó”.

- Deduplicate theo cặp User — place — ngày lịch.
- Trong cùng một ngày, nhiều lần bấm của cùng User với cùng place chỉ tạo một visit event.
- Sang ngày mới, cùng cặp User — place được phép tạo một visit event mới.
- Visit của khách chưa đăng nhập được tính vào hot; hệ thống dùng cookie trình duyệt chứa random ID, chỉ lưu hash của ID và không lưu IP thô.
- Với cùng một cookie, một place và một ngày chỉ tạo tối đa một anonymous visit event. Nếu cookie bị xóa hoặc hết hiệu lực, trình duyệt được xem như khách mới.

### 5.5. Lịch sử đi tới

User có thể truy cập lịch sử các place đã bấm “Đi tới đó”.

Đã xác nhận:

- Hệ thống chỉ lưu **visit event**, tức lần bấm “Đi tới đó”.
- Hệ thống **không** lưu recommendation/roll event thành bảng dữ liệu riêng.
- Từ lịch sử, User có thể thực hiện rating/review.

Hệ quả cần lưu ý: dữ liệu cá nhân hóa trong tương lai sẽ được xây trên visit event, bookmark và rating chứ không dựa trên toàn bộ lịch sử random. Nếu sau này cần phân tích tỉ lệ roll/skip, đó là một quyết định mở rộng riêng và phải được duyệt bổ sung.

### 5.6. Rating, review và comment

Quy tắc đã chốt:

- **Điều kiện rating:** User chỉ được rating một place khi đã có ít nhất một visit event với place đó. Rating phản ánh trải nghiệm thực tế, không phải cảm nhận từ danh sách.
- **Số lượng rating:** mỗi User chỉ có **một** review duy nhất cho một place. Lần đánh giá sau là cập nhật review hiện có, không tạo bản ghi mới.
- **Review:** bắt buộc có rating sao; nội dung văn bản là tùy chọn; có thể đính kèm nhiều ảnh.
- **Comment:** không yêu cầu visit event và không yêu cầu rating; chỉ yêu cầu đăng nhập. Một user có thể tạo nhiều comment cho một place; mỗi comment có thể đính kèm nhiều ảnh.
- **Reply:** comment có thể trả lời comment khác bằng `parent_id`. User, Sub-admin và Admin có thể reply theo quyền; Sub-admin chỉ được reply nội dung thuộc place mình quản lý. Review không có reply trực tiếp.
- **Mô hình dữ liệu:** review/rating và comment là hai loại nội dung riêng biệt. Review gắn với ràng buộc một-User-một-place; comment/câu hỏi không có ràng buộc đó và có thể có nhiều bản ghi.

Chi tiết khác:

- Rating theo thang 1–5 sao, cho phép bước 0.5 sao.
- User được sửa và xóa review và comment của chính mình.
- Admin có quyền ẩn, gỡ hoặc kiểm duyệt nội dung vi phạm.

> **TBD:** Có cần báo cáo nội dung và cơ chế moderation chi tiết không.

### 5.7. Trang chủ và địa điểm hot

Trang chủ có thể hiển thị:

- Khu vực bắt đầu khám phá.
- Các category.
- Bộ lọc nhanh.
- Nút bắt đầu random.
- Các địa điểm hot/nổi bật.
- Khu vực request quảng bá để Admin xem xét trong phạm vi MVP; chưa có cơ chế placement công khai.

Địa điểm hot được xác định dựa trên tổng số visit event của User đã đăng nhập và khách chưa đăng nhập trong 30 ngày gần nhất.

- Hot tự nhiên chỉ dùng tổng lượt visit trong cửa sổ 30 ngày.
- MVP chưa thay đổi thứ tự hiển thị place theo promotion request đã approved.

Phải phân biệt rõ:

- **Hot tự nhiên:** xếp hạng theo tổng visit event trong 30 ngày gần nhất.
- **Promotion request:** request do Sub-admin gửi để Admin ghi nhận/quyết định; cơ chế hiển thị quảng bá là phạm vi mở rộng sau MVP.

### 5.8. Yêu cầu thêm địa điểm

User có thể gửi request khi place chưa tồn tại.

Luồng cơ bản:

1. User nhập thông tin place gồm tên place, Google Maps URL, địa chỉ text và một category; ảnh được khuyến khích đính kèm để tăng độ tin cậy.
2. Hệ thống tạo request ở trạng thái `pending`.
3. Admin tự xem xét khả năng trùng trong quá trình duyệt dựa trên thông tin request và dữ liệu hiện có.
4. Admin chuẩn hoá dữ liệu cần thiết rồi chuyển request sang `approved` hoặc `rejected`.
5. Khi approved, place được tạo/kích hoạt theo quy trình dữ liệu đã được thiết kế.

Đã chốt: không tự động phát hiện trùng; Admin tự xem xét khi duyệt. Việc Admin chuẩn hoá dữ liệu trước khi publish đã được chốt tại mục 6.3.

### 5.9. Đăng ký địa điểm và xin role Sub-admin

Người dùng có thể gửi yêu cầu vừa thêm địa điểm vừa đăng ký làm người quản lý địa điểm.

Thông tin request dự kiến gồm:

- Thông tin place.
- Thông tin người đại diện/quản lý.
- Email liên hệ và email dự kiến dùng để đăng nhập.
- Thông tin chứng minh quyền quản lý nếu cần.

Luồng bắt buộc đã xác nhận:

1. Người dùng gửi thông tin place, email và thông tin người đại diện/quản lý.
2. Request chờ Admin duyệt.
3. Admin duyệt cả địa điểm và quyền Sub-admin.
4. Chỉ sau khi cả hai phần được duyệt, hệ thống mới tạo tài khoản, gửi email yêu cầu đặt mật khẩu, kích hoạt quyền quản lý và gắn Sub-admin vào place.
5. Ứng viên tự đặt mật khẩu mới qua liên kết/token một lần trong email.
6. Nếu một trong hai phần bị từ chối, không tạo tài khoản và không cấp quyền Sub-admin cho place đó.

**Đã chốt về tài khoản ứng viên:**

- `manager_applications` chỉ lưu email và thông tin hồ sơ cần thiết; không lưu mật khẩu, password hash hoặc plaintext credential.
- Hệ thống **không** tạo bản ghi `users` khi đơn được gửi.
- Chỉ sau khi Admin duyệt cả place và role Sub-admin, hệ thống mới tạo bản ghi `users`, gán role Sub-admin và tạo quan hệ quản lý place.
- Hệ thống gửi email chứa liên kết/token một lần để ứng viên tự đặt mật khẩu mới; chỉ lưu hash của token và thời hạn sử dụng.
- Nếu đơn bị từ chối, không có tài khoản nào được tạo từ đơn đó.

### 5.10. Quản lý place bởi Sub-admin

Sub-admin chỉ được thao tác trong phạm vi place được cấp quyền.

Các nhóm dữ liệu có thể cập nhật:

- Giờ mở cửa.
- Bộ ảnh của place, bao gồm ảnh menu nếu địa điểm có và muốn cung cấp.
- Thumbnail của place.
- Thông tin mô tả được phép chỉnh sửa.
- Phản hồi review/comment/câu hỏi.
- Request quảng bá.

Các thay đổi của Sub-admin có hiệu lực ngay và không cần Admin duyệt lại trước khi hiển thị công khai. Admin vẫn có quyền can thiệp, ẩn hoặc gỡ dữ liệu vi phạm sau đó.

#### Ảnh — đã chốt

- Hệ thống chỉ có một bảng `place_images`, lưu quan hệ giữa place và `image_url`.
- `places.thumbnail_image_id` trỏ tới một record trong `place_images` để chọn ảnh chính trên card random.
- Các ảnh còn lại của place hiển thị trong bộ ảnh ở trang detail; không phân biệt menu/gallery trong database.
- Ảnh menu nếu có cũng là ảnh trong cùng bộ ảnh và không bắt buộc.
- Seed ban đầu chỉ lưu URL thumbnail từ CSV; không tải ảnh về storage.
- Sub-admin upload ảnh lên storage phù hợp, sau đó lưu public URL; khi đổi thumbnail chỉ cần thêm/chọn record ảnh rồi cập nhật `thumbnail_image_id`.
- Khi xóa ảnh đang là thumbnail, hệ thống đặt `thumbnail_image_id` về `null` trước khi xóa ảnh.
- Admin có quyền ẩn hoặc gỡ ảnh.
- User **không** upload ảnh trong phiên bản hiện tại.

### 5.11. Quảng bá địa điểm

Sub-admin có thể gửi request để Admin xem xét việc quảng bá địa điểm mình quản lý.

Phạm vi MVP:

1. Sub-admin chọn place đủ quyền quản lý.
2. Gửi request quảng bá.
3. Admin xem xét và approved/rejected.
4. Hệ thống chỉ lưu request và quyết định của Admin.
5. Chưa triển khai placement, vị trí, nhãn, thời hạn, package, phí hoặc thanh toán.

Các cơ chế hiển thị quảng bá là phạm vi mở rộng sau MVP.

## 6. Business rules tổng hợp

### 6.1. Place

- Mỗi place có đúng **một category**; AI có thể hỗ trợ phân loại place vào một category phù hợp từ danh sách category hợp lệ.
- Place có thể có nhiều tags.
- Tag được gán cho place phải thuộc allowlist tag active toàn cục và không phụ thuộc category.
- Place có thể có giờ mở cửa, mức giá và bộ ảnh tùy loại.
- Chỉ place có `status = active` xuất hiện trong random và trang công khai; `pending` và `rejected` chỉ thuộc các bảng request.

#### Mô hình khu vực và vị trí — đã chốt

- Chỉ dùng một bảng khu vực chứa **quận/huyện/thị xã thuộc Hà Nội**.
- Không lưu tỉnh/thành phố và không mô hình hóa phường/xã.
- AI chọn quận/huyện/thị xã từ danh sách hợp lệ dựa trên toàn bộ địa chỉ chi tiết được cung cấp.
- Place lưu `address_text` riêng để hiển thị, cùng `latitude`/`longitude` để mở Google Maps và tính khoảng cách.
- Tọa độ là dữ liệu bắt buộc với place.

#### Giờ mở cửa — đã chốt

- Giờ mở cửa được khai báo theo **thứ trong tuần**.
- Một ngày có thể có **nhiều khung giờ**, ví dụ nghỉ trưa.
- Hỗ trợ khung giờ **qua nửa đêm**, ví dụ 18:00 đến 02:00.
- Ngày lễ và ngoại lệ theo ngày cụ thể **không** thuộc phạm vi hiện tại.

### 6.2. Category và tag

Bộ tag active cốt lõi gồm: Chill, Yên tĩnh, Sang trọng, Hẹn hò, Đi nhóm, Gia đình, Học sinh — sinh viên, Trẻ em, Chấp nhận pet, Có chỗ đỗ xe, Ngoài trời, Mở khuya, Đồ ăn đường phố, Ăn nhanh, Đồ chay, Đồ ngọt và Bia & nhậu.

Một place có thể gắn nhiều tags. Category và tag là hai chiều phân loại độc lập; người dùng có thể filter đồng thời theo cả hai. Tag đã ngừng sử dụng được chuyển sang `inactive` để bảo toàn quan hệ `place_tags` lịch sử nhưng không xuất hiện trong lựa chọn mới hoặc prompt AI.

> **TBD:** Tag có đa ngôn ngữ không, có phân cấp/nhóm tag không, User có được đề xuất tag không, và Admin có được đổi tên/xóa tag đã được dùng không.

### 6.3. Quyền và duyệt

- Frontend guard không thay thế authorization ở backend.
- Admin là bên duyệt request và cấp quyền Sub-admin.
- Sub-admin chỉ thao tác với place được gán.
- Request có tối thiểu trạng thái `pending`, `approved`, `rejected`.
- Mọi thao tác duyệt quan trọng nên lưu người duyệt, thời điểm và lý do nếu bị từ chối.

#### Quan hệ Sub-admin và place — đã chốt

- Quan hệ là **nhiều-nhiều**.
- Một Sub-admin có thể quản lý nhiều place.
- Một place có thể có nhiều Sub-admin cùng phụ trách.
- Mọi thao tác của Sub-admin phải kiểm tra quyền trên đúng place mục tiêu, không dựa vào role chung.

#### Duyệt place request — đã chốt

- Hệ thống **không** tự động tạo place từ dữ liệu thô của request.
- Admin xem, chỉnh sửa và chuẩn hoá dữ liệu như tên, địa chỉ, toạ độ, category và tags trước khi publish.
- Chỉ sau bước chuẩn hoá và duyệt, place mới chuyển sang trạng thái active và xuất hiện công khai.
- Dữ liệu gốc do người gửi cung cấp được giữ lại trong request để đối chiếu.

### 6.4. Dữ liệu cá nhân và mật khẩu

- Mật khẩu tài khoản đã tạo phải được hash, không lưu plaintext.
- `manager_applications` không lưu mật khẩu hoặc password hash.
- Sau khi được duyệt, ứng viên đặt mật khẩu mới qua liên kết/token một lần gửi bằng email.
- Token đặt mật khẩu chỉ lưu dưới dạng hash, có thời hạn và không được lưu plaintext sau khi phát hành.
- Không đưa mật khẩu, token plaintext hoặc credential vào log, response API hay tài liệu mẫu.
- Thông tin đăng ký Sub-admin cần được bảo vệ và chỉ người có quyền mới được xem.
- Cần có chính sách khóa/xóa tài khoản và xử lý dữ liệu khi role bị thu hồi.

## 7. Sơ bộ mô hình dữ liệu nghiệp vụ

Đây là danh sách thực thể định hướng để QA và thiết kế database sau này, chưa phải schema cuối cùng:

- `users`: tài khoản người dùng.
- `roles`: vai trò User, Sub-admin, Admin.
- `user_roles`: quan hệ tài khoản — vai trò nếu cần hỗ trợ nhiều role.
- `places`: thông tin địa điểm, gồm `category_id`, `district_id`, `google_place_id`, `phone`, `website_url`, `google_maps_url`, địa chỉ, `latitude`, `longitude`, `min_price`, `max_price`, `thumbnail_image_id` và trạng thái.
- `categories`: danh mục địa điểm; mỗi place chỉ tham chiếu một category.
- `districts`: một danh sách phẳng các quận/huyện/thị xã thuộc Hà Nội.
- `tags`: tag mô tả.
- `place_tags`: quan hệ place — tag.
- `place_opening_hours`: khung giờ mở cửa theo thứ trong tuần, hỗ trợ nhiều khung giờ mỗi ngày, all-day, closed và qua nửa đêm; ngày thiếu là unknown.
- `place_images`: quan hệ place — image URL, dùng chung cho thumbnail và bộ ảnh detail.
- `place_managers`: quan hệ nhiều-nhiều giữa Sub-admin và place.
- `place_requests`: request thêm/cập nhật place, lưu dữ liệu gốc do người gửi cung cấp.
- `manager_applications`: request xin trở thành Sub-admin, liên kết với `place_requests`, chỉ lưu email và thông tin hồ sơ cần thiết; không lưu mật khẩu và không liên kết `users` trước khi được duyệt.
- `bookmarks`: bookmark riêng tư của User.
- `visit_events`: các lần User đã đăng nhập bấm “Đi tới đó”, deduplicate theo User — place — ngày.
- `anonymous_visit_events` hoặc cơ chế đếm tương đương: dữ liệu lượt khách chưa đăng nhập dùng cho thống kê hot, không tạo lịch sử cá nhân.
- `reviews`: rating và review, ràng buộc một bản ghi cho mỗi cặp User — place.
- `comments`: comment/câu hỏi của User và reply của Sub-admin/Admin.
- `promotion_requests`: request quảng bá và quyết định của Admin trong MVP; chưa có bảng placement.
- `moderation_actions`: lịch sử ẩn/gỡ/duyệt nội dung nếu cần audit.

Không cần bảng lưu recommendation/roll event vì danh sách bỏ qua chỉ tồn tại trong lượt khám phá phía client.

Các thực thể trên cần được kiểm tra lại trước khi tạo migration. Không nên tạo tất cả bảng chỉ dựa trên danh sách này khi các quy tắc TBD chưa được chốt.

## 8. Luồng nghiệp vụ chính

### 8.1. User khám phá và đi tới place

1. User mở trang chủ.
2. Chọn category, khu vực, mức giá, khoảng cách, giờ mở cửa và/hoặc tags.
3. Bấm random.
4. Hệ thống trả một place phù hợp.
5. User chọn một trong các hành động:
   - Bookmark.
   - Xem chi tiết.
   - Roll lại.
   - Bấm “Đi tới đó”.
6. Nếu bấm “Đi tới đó”, hệ thống ghi `visit_event` và mở Google Maps.
7. User có thể vào lịch sử để rating/review/comment theo chính sách.

### 8.2. User gửi place mới

1. User mở chức năng thêm địa điểm.
2. Nhập tên place, Google Maps URL, địa chỉ text, một category và có thể đính kèm ảnh.
3. Hệ thống tạo request `pending` mà không tự động kết luận trùng.
4. Admin xem xét dữ liệu và khả năng trùng thủ công.
5. Admin chuẩn hoá dữ liệu rồi approved hoặc rejected request.
6. Nếu approved, place được tạo/kích hoạt.
7. Nếu rejected, request kết thúc với lý do nếu chính sách yêu cầu và gửi email thông báo cho người gửi.

### 8.3. User xin làm Sub-admin

1. User nhập thông tin địa điểm và thông tin quản lý/tài khoản dự kiến.
2. Hệ thống tạo request place và request cấp role liên kết; chưa tạo `users`.
3. Admin review thông tin.
4. Admin duyệt place.
5. Admin duyệt role Sub-admin.
6. Hệ thống tạo `users`, gán role và tạo quan hệ quản lý place trong cùng luồng được bảo vệ bằng transaction.
7. Hệ thống gửi email chứa liên kết/token một lần để ứng viên tự đặt mật khẩu mới; không lưu mật khẩu trong `manager_applications`.
8. Nếu bị từ chối, không tạo tài khoản và gửi email thông báo.
8. Sub-admin đăng nhập và cập nhật dữ liệu trong phạm vi quyền.

### 8.4. Sub-admin cập nhật place

1. Sub-admin đăng nhập.
2. Chọn place được gán.
3. Chỉnh sửa giờ mở cửa, menu, ảnh hoặc nội dung được phép.
4. Hệ thống kiểm tra quyền và dữ liệu.
5. Nội dung được lưu và công khai ngay, không chuyển chờ Admin duyệt.
6. Sub-admin reply review/comment/câu hỏi thuộc place.

### 8.5. Sub-admin xin quảng bá

1. Sub-admin chọn place được gán.
2. Gửi promotion request.
3. Admin kiểm tra điều kiện.
4. Admin approved hoặc rejected.
5. Nếu approved, hệ thống áp dụng ưu tiên hiển thị trong thời gian/cấu hình đã duyệt.

## 9. Yêu cầu phi chức năng sơ bộ

### 9.1. Bảo mật

- Backend phải thực thi authentication và authorization.
- Password phải được hash bằng cơ chế chuẩn của framework.
- Dữ liệu quản trị và thông tin ứng viên Sub-admin phải giới hạn quyền truy cập.
- Validation phải thực hiện tại backend.
- Không lộ stack trace, secret hoặc thông tin nội bộ qua API.
- Các thao tác duyệt, cấp quyền, quảng bá và moderation cần được kiểm soát quyền và xử lý an toàn; audit trail chi tiết cho cập nhật của Sub-admin không thuộc phạm vi hiện tại.

### 9.2. Tính nhất quán dữ liệu

- Không cho tạo bookmark trùng User/place.
- Không cấp Sub-admin nếu place hoặc application chưa được duyệt đủ điều kiện.
- Chỉ tạo tối đa một visit event cho mỗi User — place — ngày; roll lại không tạo visit event.
- Visit của khách chưa đăng nhập được tính vào thống kê hot nhưng không lưu lịch sử cá nhân.
- Không đưa place chưa active vào random công khai.
- Bookmark của place không active được ẩn khỏi danh sách nhưng không bị xóa.
- Các nghiệp vụ duyệt nhiều đối tượng cần transaction phù hợp.

### 9.3. Hiệu năng

- Random phải phản hồi nhanh với bộ lọc thông thường.
- Các truy vấn theo category, khu vực, mức giá, trạng thái và tags cần được thiết kế có index phù hợp sau khi contract được chốt.
- Thống kê hot không nên tính lại toàn bộ dữ liệu trong mỗi request nếu dữ liệu tăng lớn.
- Danh sách review, comment, bookmark và request cần pagination.

### 9.4. Khả dụng và trải nghiệm

- Có loading, empty, error và success state cho các luồng bất đồng bộ.
- Nút “Đi tới đó” cần có label rõ ràng.
- Các control lọc và random phải thao tác được bằng bàn phím.
- Hiển thị rõ khi không có kết quả hoặc place không còn khả dụng.
- Giao diện cần responsive trên mobile vì hành vi mở bản đồ thường xảy ra trên thiết bị di động.

## 10. Màn hình dự kiến

### Public/User

- Trang chủ khám phá.
- Màn hình bộ lọc và random.
- Kết quả đề xuất.
- Chi tiết place.
- Danh sách địa điểm hot.
- Đăng nhập/đăng ký.
- Bookmark của tôi.
- Lịch sử đi tới.
- Chi tiết review/comment.
- Form request thêm place.
- Form đăng ký quản lý place/Sub-admin.

### Sub-admin

- Dashboard địa điểm được quản lý.
- Chỉnh sửa thông tin place.
- Quản lý giờ mở cửa.
- Quản lý menu.
- Quản lý ảnh.
- Danh sách và phản hồi review/comment/câu hỏi.
- Tạo và theo dõi promotion request.

### Admin

- Dashboard quản trị.
- Quản lý users/roles.
- Duyệt place requests.
- Duyệt manager applications.
- Quản lý places/categories/areas/tags.
- Quản lý nội dung review/comment/ảnh/menu.
- Duyệt promotion requests.
- Báo cáo thống kê và lịch sử thao tác.

## 11. API định hướng

API phải tuân theo envelope chung đã mô tả trong [`docs/api-response-contract.md`](docs/api-response-contract.md:1). Danh sách dưới đây chỉ là nhóm endpoint cần xem xét, chưa phải contract đã duyệt:

- Authentication: contract đăng ký, xác thực email, đăng nhập user/admin, đăng xuất và Google OAuth đã chốt tại [`docs/api-auth.md`](api-auth.md); refresh/reset password chưa thuộc phạm vi hiện tại.
- Places: danh sách, chi tiết, tìm kiếm/lọc, random.
- Categories, areas, tags.
- Bookmarks: tạo, xóa, danh sách của tôi.
- Visit history: tạo visit khi bấm “Đi tới đó”, danh sách lịch sử.
- Reviews/comments: tạo, sửa, xóa, reply, moderation.
- Place requests: tạo, xem trạng thái, admin duyệt.
- Manager applications: tạo, admin duyệt, cấp quyền.
- Sub-admin place management: cập nhật place/menu/images/hours.
- Promotion requests: tạo, theo dõi, admin duyệt.
- Hot places: danh sách hot và danh sách promoted.

Trước khi triển khai API cần chốt URL, method, payload, authorization, status code, pagination, validation error và resource response cho từng nhóm.

## 12. Các điểm cần QA / quyết định mở

### Đã chốt trong QA vòng 2

Các câu hỏi ưu tiên cao trước đây đã có quyết định và được ghi trong Phụ lục B:

1. Bắt buộc đăng nhập để rating, review và comment.
2. Menu chỉ lưu dưới dạng ảnh do Sub-admin upload; không có bảng menu có cấu trúc.
3. Chỉ Sub-admin upload ảnh.
4. Place có đúng một category và có thể có nhiều tag độc lập với category.
5. Khu vực chỉ gồm quận/huyện/thị xã Hà Nội; place vẫn lưu địa chỉ chi tiết và toạ độ.
6. Khoảng cách dùng GPS, fallback về khu vực đã chọn.
7. Giờ mở cửa theo thứ trong tuần, nhiều khung giờ, hỗ trợ qua nửa đêm.
8. Chỉ lưu visit event của User; không lưu recommendation/roll event.
9. Rating cần visit event, mỗi User một rating trên một place.
10. Rating theo thang 1–5 sao, cho phép nửa sao; User được sửa/xóa nội dung của mình.
11. Mức giá lưu bằng `min_price`/`max_price` theo số nguyên VND và lọc bằng range. Giá CSV được AI chuẩn hóa; nếu không đủ chắc chắn thì cả hai giá để `null`.
12. Admin chuẩn hoá dữ liệu trước khi publish place.
13. Quan hệ Sub-admin — place là nhiều-nhiều.
14. Không tự động phát hiện trùng; Admin tự xem xét request.
15. Chỉ tạo `users` sau khi Admin duyệt place và role Sub-admin; đơn bị từ chối không tạo tài khoản.
16. Visit User deduplicate theo User — place — ngày; anonymous visit tính vào hot nhưng không vào lịch sử.
17. Bookmark của place không active được ẩn, không xóa.
18. Hot tự nhiên tính tổng visit User và anonymous trong 30 ngày gần nhất.
19. Sub-admin cập nhật có hiệu lực ngay, không cần Admin duyệt lại.
20. Request được duyệt/từ chối thì gửi thông báo qua email.
21. Không lưu lịch sử thay đổi cho cập nhật của Sub-admin.

### Ưu tiên cao còn lại — cần chốt trước thiết kế database

Không còn câu hỏi ưu tiên cao thuộc phạm vi QA vòng 3.

### Ưu tiên trung bình

1. Có báo cáo nội dung, block user và chống spam không.
2. User có được đề xuất tag mới không; Admin đổi tên hoặc xóa tag đã dùng thì xử lý thế nào.
3. Có hỗ trợ đa ngôn ngữ không.
4. Chính sách reset/forgot password và vòng đời token dài hạn; email verification và login hiện đã chốt.
6. Cơ chế mở rộng quảng bá sau MVP: nhãn, vị trí, thời hạn, package, phí và thanh toán.

## 13. Tiêu chí nghiệm thu nghiệp vụ sơ bộ

### Khám phá

- Khi bộ lọc hợp lệ và có dữ liệu, hệ thống trả một place phù hợp.
- Khi người dùng roll lại, place vừa bị bỏ qua không được chọn lại trong cùng lượt hiện tại.
- Khi không có dữ liệu, hệ thống hiển thị empty state hữu ích.
- Chỉ place có `status = active` xuất hiện trong kết quả công khai; `hidden` không xuất hiện.

### Đi tới và lịch sử

- User đã đăng nhập bấm “Đi tới đó” tạo tối đa một event cho cùng place trong cùng ngày.
- Roll lại không tạo visit event.
- Google Maps được mở với đích là place đã chọn.
- User có thể xem các place đã bấm “Đi tới đó”.

### Bookmark

- User đăng nhập có thể bookmark place.
- Bookmark trùng bị ngăn chặn.
- User chỉ thấy bookmark của chính mình.
- Bỏ bookmark cập nhật trạng thái chính xác.

### Request và phân quyền

- User có thể gửi request place mới.
- Request mới có trạng thái pending.
- Admin tự xem xét khả năng trùng, chuẩn hoá dữ liệu và có thể approved/rejected.
- Kết quả duyệt request được gửi qua email cho người gửi.
- Sub-admin chỉ truy cập place được cấp quyền.
- Tài khoản Sub-admin chỉ được tạo/kích hoạt sau khi cả place và role được duyệt.

### Nội dung địa điểm

- Sub-admin có thể cập nhật dữ liệu được phép và thay đổi có hiệu lực ngay.
- Menu, ảnh và giờ mở cửa được cập nhật trực tiếp theo quyền của Sub-admin.
- Sub-admin có thể reply nội dung thuộc place mình quản lý.
- Admin có thể ẩn hoặc xử lý nội dung vi phạm sau đó.

## 14. Rủi ro và lưu ý

- Nếu dữ liệu place không được chuẩn hóa, random theo category/tag/khu vực sẽ cho kết quả kém tin cậy.
- Nếu coi click Google Maps là “đã tới” mà không xác minh GPS, thống kê visit chỉ phản ánh ý định đi tới.
- Quảng bá có thể làm giảm tính khách quan của random nếu triển khai placement trong tương lai; MVP hiện chỉ ghi nhận request và quyết định Admin.
- Luồng tạo tài khoản Sub-admin phải bảo vệ email và token đặt mật khẩu; không lưu mật khẩu trong request, không trả token plaintext hoặc credential qua API, log và tài liệu.
- Rating/review/comment cần chính sách moderation và chống spam trước khi mở rộng.
- Vì Sub-admin sửa trực tiếp dữ liệu công khai mà không qua duyệt, Admin vẫn cần có quyền can thiệp và xử lý nội dung vi phạm; audit log/rollback không thuộc phạm vi hiện tại.
- Thiết kế database quá sớm trước khi chốt các câu hỏi ở mục 12 có thể dẫn đến migration khó sửa và contract không ổn định.

## 15. Đề xuất bước tiếp theo

1. Chuyển các quyết định đã chốt thành domain glossary và business rules chính thức.
2. Hoàn thiện migration checklist từ ERD đã duyệt, bao gồm cookie-hash anonymous visit, token setup 24 giờ, status dạng VARCHAR, moderation polymorphic và promotion request tối giản.
3. Xác định trạng thái, transition và quyền của từng request.
4. Thiết kế API contract cho các luồng MVP đã chốt.
5. Bổ sung test case nghiệp vụ cho random, visit, bookmark, review/comment, request và approval.
6. Sau khi contract được duyệt, tạo migration/model/service/repository/controller/resource theo kiến trúc backend của repository.
7. Đồng bộ frontend pages/components/hooks/services với API contract.

---

## Phụ lục A — Thuật ngữ

| Thuật ngữ | Ý nghĩa |
|---|---|
| Place | Một địa điểm được lưu và quản lý trong hệ thống |
| Category | Danh mục loại địa điểm, ví dụ ăn uống, cafe, vui chơi |
| Tag | Từ khóa/tính từ mô tả đặc điểm hoặc không gian của place |
| Bookmark | Lưu place riêng tư để User tra cứu sau |
| Visit event | Bản ghi được tạo khi User đã đăng nhập bấm “Đi tới đó”, tối đa một lần cho mỗi place trong một ngày |
| Roll | Thao tác yêu cầu hệ thống chọn lại một place |
| User | Người dùng thông thường |
| Sub-admin | Người quản lý được Admin cấp quyền trên một hoặc nhiều place |
| Admin | Người quản trị và duyệt dữ liệu/request toàn hệ thống |
| Place request | Yêu cầu thêm hoặc đề xuất place |
| Promotion request | Yêu cầu đưa place lên vị trí nổi bật để quảng bá |

## Phụ lục B — Quyết định đã xác nhận

### Vòng QA 1 — phạm vi và nghiệp vụ nền

- Sản phẩm là nền tảng khám phá địa điểm bằng đề xuất random.
- Nhóm nhu cầu gồm ăn uống, vui chơi, cafe và các category tương tự.
- Bộ lọc gồm category, khu vực/quận, mức giá, khoảng cách, giờ mở cửa và tags.
- Một place có thể có nhiều tags.
- Category và tags là các tiêu chí độc lập; tag chỉ cần thuộc allowlist tag active toàn cục.
- Có ba vai trò: User, Sub-admin/quản lý địa điểm và Admin.
- Request có trạng thái tối thiểu: pending, approved, rejected.
- Sub-admin được Admin duyệt và chỉ quản lý place được cấp quyền.
- Sub-admin có thể cập nhật giờ mở cửa, menu, ảnh và reply nội dung của người dùng trong phạm vi place.
- Place mới có thể được User hoặc người quản lý đề xuất.
- Request xin place và role Sub-admin phải được Admin duyệt trước khi tạo/kích hoạt tài khoản tương ứng.
- Chỉ bấm “Đi tới đó” mới được tính là một lần đi tới.
- Roll lại/chọn lại không tính là đi tới.
- Google Maps được mở khi bấm “Đi tới đó”.
- User có thể xem lịch sử đi tới và dùng lịch sử để rating/review.
- User có thể comment không kèm rating; chính sách moderation vẫn cần chốt.
- Bookmark là riêng tư, chỉ User sở hữu mới xem được.
- Địa điểm hot liên quan đến nhiều lượt “Đi tới đó”.
- Sub-admin có thể gửi request để quảng bá place lên vị trí nổi bật/đầu; Admin quyết định duyệt.

### Vòng QA 2 — quyết định ưu tiên cao đã được cập nhật bởi QA vòng 3

Các quyết định nền của vòng QA 2 vẫn được giữ lại, ngoại trừ:

- Ảnh và menu dùng chung một bảng `place_images`; `thumbnail_image_id` trên `places` chọn ảnh chính, các ảnh còn lại hiển thị ở detail.
- Seed dữ liệu gốc từ nhiều CSV là pipeline nội bộ một lần: dedupe/làm sạch cục bộ trước AI, chỉ gửi record có Google place ID và thuộc Hà Nội; không gửi category nguồn từ CSV. AI dùng tên, địa chỉ chi tiết, Google Maps URL, tọa độ, mô tả và thuộc tính để chuẩn hóa địa chỉ, chọn một category hệ thống, các tag toàn cục và quận/huyện/thị xã, rồi import thẳng `active`; không pending/approve và không tạo bảng import lâu dài.
- Rating, review count, review và status từ Google không được nhập; review/rating chỉ do User HNAJ tạo.
- Tài khoản Sub-admin chỉ tạo bản ghi `users` sau khi Admin duyệt; không tạo `users` khi gửi đơn.

### Vòng QA 3 — xác nhận chi tiết

| # | Chủ đề | Quyết định |
|---|---|---|
| 1 | Bộ lọc | Tất cả bộ lọc đều tuỳ chọn; có thể random khi không chọn bộ lọc nào |
| 2 | Mức giá | Lưu `min_price` và `max_price` theo số nguyên VND; UI dùng range slider |
| 3 | Rating | Thang 1–5 sao, cho phép nửa sao |
| 4 | Sửa/xoá nội dung | User được sửa và xoá rating, review, comment của chính mình |
| 5 | Place request | Bắt buộc tên, Google Maps URL, địa chỉ text, một category; ảnh khuyến khích |
| 6 | Phát hiện trùng | Không tự động phát hiện; Admin tự xem xét khi duyệt |
| 7 | Tài khoản Sub-admin | Chỉ tạo `users` sau khi duyệt place và role; nếu từ chối thì không tạo tài khoản |
| 8 | Visit deduplication | Một visit cho mỗi User — place — ngày; sang ngày mới được tạo event mới |
| 9 | Anonymous visit | Tính vào hot, không lưu lịch sử cá nhân |
| 10 | Bookmark không active | Ẩn khỏi danh sách, giữ bản ghi để có thể hiển thị lại khi place được khôi phục |
| 11 | Cập nhật Sub-admin | Có hiệu lực ngay, không cần Admin duyệt lại |
| 12 | Hot | Tổng visit User và anonymous trong 30 ngày gần nhất |
| 13 | Notification | Gửi email khi request được approved hoặc rejected |
| 14 | Lịch sử thay đổi | Không lưu audit log cho cập nhật của Sub-admin |
