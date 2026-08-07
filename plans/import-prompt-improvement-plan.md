# Plan — Cải thiện prompt AI cho place CSV import + QA dữ liệu

- **Trạng thái:** Chờ duyệt
- **Ngày soạn:** 2026-08-06
- **Phạm vi:** Backend import pipeline ([`hnaj-be/app/Services/PlaceImport/`](../hnaj-be/app/Services/PlaceImport/PlaceImportPrompt.php)) + test + QA dữ liệu hiện có
- **Quy tắc áp dụng:** AGENTS.md mục 5.2 (kế hoạch chờ duyệt), 7 (kiến trúc backend), 12 (kiểm thử)

---

## 1. Vấn đề (hành vi hiện tại)

Dữ liệu import đang "rác":

- `mua sắm` chứa Co-op Mart; mục tiêu của category này là **trung tâm thương mại lớn, sang trọng** (Vincom Center, Lotte Mall, Hanoi Center, Aeon Mall), KHÔNG phải siêu thị/chuỗi bán lẻ (WinMart, Co-op Mart, Fujimart).
- Nhiều place hoàn toàn không liên quan category nhưng vẫn bị nhét vào (ví dụ Fujimart vào "vui chơi/giải trí").

**Nguyên nhân gốc** trong [`PlaceImportPrompt.php`](../hnaj-be/app/Services/PlaceImport/PlaceImportPrompt.php:21):

1. Rule dòng 21 ép AI luôn chọn category: *"The supplied categories are broad and exhaustive for this import. Select the closest valid category instead of rejecting a record merely because its source place type is more specific."* → AI không bao giờ được phép loại record.
2. Taxonomy gửi cho AI chỉ có `id/slug/name` ([`TaxonomyProvider.php`](../hnaj-be/app/Services/PlaceImport/TaxonomyProvider.php:20)) — **không có định nghĩa category**, AI không thể biết "mua sắm" nghĩa là TTTM lớn chứ không phải siêu thị.
3. Prompt không nêu phạm vi sản phẩm (nền tảng ăn uống/vui chơi/giải trí), nên AI không có cơ sở từ chối cửa hàng tạp hóa, văn phòng, trường học...

## 2. Hành vi mong muốn

- AI nhận **định nghĩa từng category** (mô tả + ví dụ thuộc + ví dụ loại trừ).
- AI **bắt buộc loại** record không khớp rõ ràng với category nào: `error=true`, `error_reason=out_of_scope` — không force-fit.
- Danh sách loại trừ tường minh: siêu thị, cửa hàng tiện lợi, chợ, văn phòng, trường học, bệnh viện, ngân hàng, tòa nhà hành chính...
- Pipeline hiện tại đã xử lý sẵn `error=true`: seeder log warning + skip ([`PlaceCsvImportSeeder.php`](../hnaj-be/database/seeders/PlaceCsvImportSeeder.php:140)) → **không cần sửa seeder/validator/persistence**, chỉ sửa prompt + test.

## 3. Thay đổi dự kiến

### 3.1. [`PlaceImportPrompt.php`](../hnaj-be/app/Services/PlaceImport/PlaceImportPrompt.php) — viết lại phần rules

**Bỏ** rule "broad and exhaustive... select the closest valid category".

**Thêm** khối `product_scope`:

```text
HNAJ is a discovery platform for eating, drinking, and entertainment places in Hanoi.
Import only places a person would visit to eat, drink, have fun, relax, or explore.
Records outside this scope must be rejected, never force-classified.
```

**Thêm** khối `category_definitions` (key theo slug, khớp 8 category trong [`CategorySeeder`](../hnaj-be/database/seeders/CategorySeeder.php:21)). **Đã QA từng category với user ngày 2026-08-06:**

**`an-uong`** — Nơi hoạt động chính là ĂN tại chỗ hoặc mua đồ ăn mang đi. Đồ ăn là sản phẩm chính, không phải đồ uống.
- ✅ Thuộc: nhà hàng (Việt/Âu/Á), quán phở/bún chả/bún ốc/bún đậu, cơm bình dân/cơm văn phòng, quán nhậu & bia hơi có đồ ăn, đồ ăn đường phố có điểm bán cố định (xe bánh mì, quán ốc vỉa hè có bàn ghế), quán lẩu/nướng, tiệm bánh có đồ ăn mặn, quầy food court riêng lẻ trong TTTM, quán ăn trong chợ, bakery chỉ bán bánh.
- ❌ Loại: quán chỉ bán đồ uống (→ `ca-phe-do-uong`), siêu thị/cửa hàng thực phẩm, căng tin nội bộ không mở công khai, cloud kitchen/chỉ delivery không có chỗ ngồi.

**`ca-phe-do-uong`** — Nơi ĐỒ UỐNG là sản phẩm chính. Khách đến chủ yếu để uống, có thể kèm đồ ăn nhẹ/tráng miệng nhưng đồ uống vẫn là trọng tâm.
- ✅ Thuộc: quán cafe (truyền thống, specialty, cafe sách, cafe thú cưng, cafe sân vườn), trà chanh/trà đá vỉa hè, trà sữa (kèm bánh/đồ ăn vặt), nước ép/sinh tố, quán bar không nhạc mạnh phục vụ cocktail/mocktail, beer club nhạc nhẹ.
- ❌ Loại: nhà hàng đồ ăn là chính (→ `an-uong`), siêu thị/cửa hàng bán đồ uống đóng chai, bar/club có nhạc DJ/sàn nhảy (→ `vui-choi-giai-tri`), quán nước mía/giải khát vỉa hè chỉ mang đi không chỗ ngồi, chè/kem/tào phớ (→ `an-uong`).

**`vui-choi-giai-tri`** — Nơi khách đến chủ yếu để VUI CHƠI, GIẢI TRÍ, TRẢI NGHIỆM hoạt động — không phải để ăn uống hay mua sắm.
- ✅ Thuộc: rạp chiếu phim, khu arcade/game center, karaoke, bar/pub/club có nhạc mạnh hoặc sàn nhảy (tag: bia, dj), cafe acoustic/cafe nhạc sống, boardgame cafe (trả phí theo giờ chơi), công viên giải trí có trò chơi thu phí, khu vui chơi trẻ em trong nhà (khu riêng có tên riêng trong TTTM cũng thuộc), billiards/bida, bowling, escape room, laser tag, sân trượt patin/băng, VR center, sân khấu/ca nhạc live, sân vận động (xem thi đấu).
- ❌ Loại: cửa hàng bán lẻ (Fujimart KHÔNG phải giải trí), siêu thị, góc tiện ích trẻ em không tên riêng trong TTTM.

**`van-hoa-tham-quan`** — Điểm đến văn hóa, lịch sử, nghệ thuật — khách đến để THAM QUAN, TÌM HIỂU, CHIÊM NGƯỠNG.
- ✅ Thuộc: bảo tàng, đền/chùa/nhà thờ có giá trị tham quan, di tích lịch sử (Văn Miếu, Nhà tù Hỏa Lò, Hoàng thành), gallery/phòng tranh, nhà hát/nhà triển lãm, làng nghề truyền thống (Bát Tràng, Vạn Phúc), phố cổ & phố đi bộ Hồ Gươm, điểm check-in nghệ thuật (mural wall, sắp đặt), phố chuyên doanh di sản (Hàng Mã mùa lễ hội).
- ❌ Loại: trung tâm thương mại (→ `mua-sam`), cửa hàng lưu niệm đơn lẻ, trường học/đại học, tòa nhà văn phòng, hiệu sách lớn (mua sắm là chính), chợ truyền thống (Đồng Xuân), phố chuyên doanh không phải di sản.

**`mua-sam`** — CHỈ trung tâm thương mại (TTTM) lớn, hiện đại, sang trọng — tổ hợp mua sắm + ăn uống + giải trí trong một tòa nhà. AI đánh giá theo danh sách ví dụ, không cần ngưỡng cứng.
- ✅ Thuộc: Vincom Center (Bà Triệu, Metropolis, Royal City, Times City...), Lotte Mall/Lotte Center, Aeon Mall (Hà Đông, Long Biên), Tràng Tiền Plaza, Hanoi Center, The Garden, Indochina Plaza, Mipec Tower, các TTTM tổng hợp quy mô lớn tương đương.
- ❌ Loại: siêu thị đơn lẻ (WinMart, Co-op Mart, Co-opXtra, Fujimart, BRG Mart, Hapro, Mega Market), cửa hàng tiện lợi (Circle K, WinMart+, GS25, 7-Eleven), chợ (Đồng Xuân, chợ Hôm, chợ đêm), cửa hàng đơn lẻ (thời trang, điện máy, hiệu sách), showroom, outlet/warehouse sale, siêu thị nằm trong TTTM (chỉ import TTTM, không import riêng siêu thị bên trong).

**`the-thao-van-dong`** — Nơi khách đến để VẬN ĐỘNG, TẬP LUYỆN hoặc CHƠI THỂ THAO — hoạt động thể chất là mục đích chính.
- ✅ Thuộc: phòng gym/fitness center, bể bơi công cộng, sân bóng đá/tennis/cầu lông/bóng rổ (cho thuê sân), phòng yoga/pilates, sân golf, leo núi trong nhà (climbing gym), sân patin/trượt băng, pickleball/padel, trung tâm thể thao tổng hợp, gym kết hợp spa/sauna nếu tập là chính.
- ❌ Loại: cửa hàng bán đồ thể thao, sân vận động chỉ để xem (→ `vui-choi-giai-tri`), công viên có máy tập miễn phí (→ `thien-nhien-ngoai-troi`), câu lạc bộ chạy bộ/đạp xe không có địa điểm vật lý cố định.

**`thu-gian-lam-dep`** — Nơi khách đến để THƯ GIÃN, CHĂM SÓC cơ thể hoặc LÀM ĐẸP — trải nghiệm nghỉ ngơi, phục hồi, thẩm mỹ là mục đích chính.
- ✅ Thuộc: spa, massage (thái, đá nóng, foot massage), xông hơi/tắm lá thuốc, salon tóc, tiệm nail/mi, tiệm làm đẹp thẩm mỹ không xâm lấn, onsen/tắm khoáng kiểu Nhật, tiệm gội đầu dưỡng sinh, spa trong khách sạn 5 sao nếu mở cửa cho khách ngoài.
- ❌ Loại: thẩm mỹ viện y khoa/phẫu thuật thẩm mỹ/tiêm filler/nha khoa thẩm mỹ (loại hẳn — y tế), bệnh viện/phòng khám da liễu, cửa hàng bán mỹ phẩm, gym/fitness (→ `the-thao-van-dong`).

**`thien-nhien-ngoai-troi`** — Không gian thiên nhiên, cây xanh, mặt nước — khách đến để TẬN HƯỞNG KHÔNG GIAN, dạo chơi, picnic, ngắm cảnh. Thiên nhiên là sản phẩm chính.
- ✅ Thuộc: công viên (Thống Nhất, Thủ Lệ, Yên Sở), vườn hoa, vườn thực vật, vườn thú, phố đi bộ ven hồ, bãi giữa sông Hồng/bãi đá sông Hồng (nếu đủ thông tin địa chỉ/tọa độ), điểm picnic/cắm trại ngoại thành, đường dạo ven hồ Tây, núi/trang trại trải nghiệm ngoại thành (Ba Vì, Sóc Sơn), khu sinh thái có bán vé ngày, công viên có máy tập thể dục miễn phí.
- ❌ Loại: công viên giải trí có trò chơi thu phí (→ `vui-choi-giai-tri`), quán cafe sân vườn (→ `ca-phe-do-uong`), nhà hàng có vườn (→ `an-uong`), resort/khu nghỉ dưỡng đóng (lưu trú).

**Thêm** khối `reject_rules`:

```text
If a place does not clearly and genuinely fit exactly one category, set error=true with
error_reason=out_of_scope. Never force a place into the nearest category.
Always reject: supermarkets and grocery stores (WinMart, WinMart+, Co-op Mart, Co-opXtra,
Fujimart, BRG Mart, Hapro), convenience stores (Circle K, GS25, 7-Eleven), wet markets,
offices, schools, universities, hospitals, clinics, banks, government buildings, residential
buildings, gas stations, showrooms, warehouses, pure lodging without a dining/entertainment focus.
```

**Giữ nguyên** các rule còn lại (price normalization, opening hours, address, tags, error shape, self-check).

### 3.2. [`PlaceImportTest.php`](../hnaj-be/tests/Unit/PlaceImportTest.php:159) — cập nhật + thêm test

- Cập nhật `test_prompt_uses_id_contract_and_does_not_contain_local_import_fields`: assert prompt KHÔNG còn chứa "broad and exhaustive", CÓ chứa `out_of_scope` và `category_definitions`.
- Thêm test mới: mọi category slug trong taxonomy truyền vào đều xuất hiện trong prompt (chống trôi định nghĩa khi thêm category mới).

### 3.3. QA dữ liệu hiện có (cần bạn chọn phương án — mục 5)

Prompt mới chỉ ảnh hưởng lần import **tương lai**. Place rác đã import KHÔNG được phân loại lại vì [`PlaceDuplicateDetector`](../hnaj-be/app/Services/PlaceImport/PlaceDuplicateDetector.php) skip theo `google_place_id`. Phương án:

- **A (khuyến nghị, không phá hủy):** tạo artisan command read-only `places:audit-import` — liệt kê place theo category (tên + địa chỉ), cờ đỏ các chuỗi siêu thị/cửa hàng tiện lợi theo tên để bạn rà soát.
- **B (cần duyệt riêng, phá hủy):** sau khi rà soát từ A, hide (`status=hidden`) hoặc soft-delete các place rác đã xác nhận.

## 4. Ảnh hưởng

- **API/database/frontend/Docker:** không đổi contract, không migration, không đổi FE. Chỉ sửa nội dung prompt gửi AI và test.
- **Tương thích ngược:** output schema giữ nguyên (`error/error_reason/...`), validator không đổi.
- **Chi phí AI:** prompt dài hơn (~1.5–2KB) — không đáng kể với batch 10 record.

## 5. Kiểm chứng

- Chạy test trong Docker: `docker compose --env-file .env exec backend php artisan test --filter=PlaceImportTest`.
- Chạy lint/format backend nếu repo có script (kiểm tra `composer.json` trước).
- Tùy chọn: chạy thử seeder với 1 file CSV nhỏ (nếu còn file `hanoi_Z*.csv`) để xem tỉ lệ `ai_errors/out_of_scope` tăng đúng kỳ vọng — cần bạn xác nhận vì tốn AI credit.

## 6. Giả định, rủi ro, điểm cần xác nhận

1. **Định nghĩa `mua-sam`:** chốt "chỉ TTTM lớn" gồm Vincom/Lotte/Aeon/Hanoi Center/Tràng Tiền Plaza? Có loại trừ hoàn toàn siêu thị và chợ không? (Mặc định: CÓ loại trừ.)
2. **Vị trí định nghĩa category:** hard-code trong `PlaceImportPrompt` (khuyến nghị — đây là policy của riêng pipeline import, không cần migration). Phương án thay thế: thêm cột `description` vào bảng `categories` — cần duyệt migration riêng.
3. **Dữ liệu rác hiện có:** chọn phương án A hay A+B ở mục 3.3?
4. **Rủi ro:** định nghĩa quá chặt có thể loại nhầm place hợp lệ (ví dụ tổ hợp ăn uống trong chợ). Mitigation: rule "reject only when clearly out of scope" + bạn rà soát output audit.
5. Prompt viết bằng tiếng Anh (nhất quán prompt hiện tại, AI xử lý tốt hơn); ví dụ tên riêng giữ nguyên tiếng Việt.

## 7. Bước triển khai (sau khi duyệt)

1. Sửa [`PlaceImportPrompt.php`](../hnaj-be/app/Services/PlaceImport/PlaceImportPrompt.php) theo mục 3.1.
2. Cập nhật + thêm test theo mục 3.2.
3. Chạy test trong Docker Compose.
4. (Nếu chọn A) Tạo command audit read-only + chạy thử.
5. Bàn giao: diff, kết quả test, hướng dẫn rà soát dữ liệu.
