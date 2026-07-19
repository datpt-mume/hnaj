# Recommendation Policy

## Trạng thái

Backend đã có recommendation cơ bản. Các quy tắc open-now, chống lặp 24 giờ, Bayesian rating và weighted random vẫn là mục tiêu chưa hoàn thiện.

## Eligibility

Recommendation chỉ xét Place đã `published` và chưa soft-delete. Quận, category và khoảng ngân sách là hard constraint ở mọi cấp. Place phù hợp ngân sách khi giá trung bình của `price_min`/`price_max` nằm trong khoảng request; `price_max: null` nghĩa là không có trần.

`location` là mốc tính bán kính. Frontend ưu tiên GPS khi khách cho phép; nếu không, dùng centroid các place published trong quận do `/districts` trả về và phải nói rõ mốc này. Quận vẫn được lọc cứng dù GPS nằm gần ranh giới.

## Tags và ranking

Tag request luôn dùng slug và được match theo OR. Related tags được xếp theo số lần đồng xuất hiện trên place published cùng category. Toàn bộ tag vẫn được trả về; tag selected và related chỉ được đẩy lên trước.

1. mức độ khớp tag;
2. khoảng cách;
3. Bayesian rating.

Hệ số cụ thể là cấu hình/TBD và phải được benchmark trước khi implementation. Production không nhận random seed từ client; test/local có thể dùng seed nội bộ để tái lập.

## Fallback

Backend xử lý cascade trong một request. Dừng khi đủ `limit`; nếu không đủ sau mọi cấp, trả số kết quả tốt nhất hiện có.

1. Query gốc với bán kính đã chọn và tag scoring.
2. Mở rộng bán kính `1.5x`.
3. Giảm yêu cầu điểm/tag phù hợp.
4. Bỏ tag filter.
5. Mở rộng bán kính `2x`.

Bán kính cuối cùng không vượt 20 km. Không có cấp nào nới budget. FE chỉ hiển thị loading chung và meta message cuối cùng.

## Chống lặp

Mọi Place đã được gợi ý trong 24 giờ gần nhất bị loại khỏi pool ưu tiên. Anonymous guest dùng functional anonymous ID cookie; user đăng nhập dùng user ID. Khi pool không đủ, hệ thống có thể trả lại các Place cũ hơn 24 giờ. Favorite không có ngoại lệ riêng trong quy tắc 24 giờ.

Recommendation history giữ 30 ngày. User không có thao tác reset hoặc xem history trong MVP.

## Response metadata

Response cần cho biết `fallback_applied`, `fallback_level`, `query_radius_km`, số lượng match và message key ổn định. Câu hiển thị tiếng Việt do FE dịch từ message key.
