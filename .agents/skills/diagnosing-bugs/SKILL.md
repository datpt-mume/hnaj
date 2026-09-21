---
name: diagnosing-bugs
description: "Vòng lặp chẩn đoán có phương pháp cho các lỗi khó (hard bugs) và suy giảm hiệu năng. Sử dụng khi người dùng nói 'diagnose'/'debug this', hoặc báo cáo lỗi bị crash/throw/chậm."
---

# Chẩn Đoán Lỗi (Diagnosing Bugs)

Kỷ luật chẩn đoán cho các lỗi khó và lỗi suy giảm hiệu năng. Chỉ bỏ qua các giai đoạn khi có lý do thực sự chính đáng.

Khi khám phá codebase, hãy đọc `CONTEXT.md` (nếu có) để nắm mô hình tư duy của các module liên quan, và kiểm tra các ADR trong khu vực đang xử lý.

## Che giấu thông tin nhạy cảm (Redact Secrets)

Kỹ năng này yêu cầu bạn hiển thị các câu lệnh, output và dữ liệu thu thập được. **Luôn ẩn mọi bí mật/token trước**: thay thế bằng `<REDACTED>`. Viết các vòng lặp kiểm tra sử dụng biến môi trường để credential nằm trong môi trường thay vì in ra màn hình. Đối với log/trace có auth header, chỉ trích dẫn các dòng phục vụ phân tích.

## Giai đoạn 1: Xây dựng Vòng lặp phản hồi (Build a Feedback Loop)

**Đây là linh hồn của toàn bộ kỹ năng.** Mọi thứ khác chỉ là thao tác cơ học. Nếu bạn có một tín hiệu Đạt/Không đạt (Pass/Fail) **chặt chẽ và tức thì** (tín hiệu chuyển sang Đỏ đối với *chính lỗi này*), bạn chắc chắn sẽ tìm ra nguyên nhân gốc rễ. Nếu không có vòng lặp này, dù bạn có nhìn chằm chằm vào code bao lâu cũng vô ích.

Hãy đầu tư nỗ lực tối đa vào bước này:

### Các cách xây dựng vòng lặp phản hồi (theo thứ tự ưu tiên):

1. **Viết một bài test thất bại (Failing test)** tại bất kỳ khớp nối (seam) nào chạm tới lỗi: unit, integration, hoặc e2e.
2. **Script Curl / HTTP** gọi trực tiếp vào dev server đang chạy.
3. **Gọi lệnh CLI** với dữ liệu đầu vào mẫu (fixture), so sánh output với kết quả chuẩn (snapshot).
4. **Script trình duyệt headless** (Playwright / Puppeteer) điều khiển giao diện và kiểm tra DOM/console/network.
5. **Phát lại vết lỗi thực tế (Replay trace):** Lưu một request / payload / log sự kiện thực tế từ môi trường lỗi về đĩa; phát lại nó độc lập qua luồng code.
6. **Harness cô lập tối thiểu:** Khởi động một phần nhỏ nhất của hệ thống (một service, mock các phụ thuộc) để kích hoạt đoạn code lỗi chỉ bằng một lời gọi hàm duy nhất.
7. **Vòng lặp kiểm thử ngẫu nhiên (Fuzz loop):** Nếu lỗi xuất hiện chập chờn, hãy chạy 1.000 đầu vào ngẫu nhiên để ép lỗi lộ diện.
8. **Script phân đôi nhị phân (Bisection harness):** Nếu lỗi xuất hiện giữa 2 trạng thái/commit, tự động hóa việc kiểm tra để chạy `git bisect run`.
9. **So sánh vi phân (Differential loop):** Chạy cùng một input qua phiên bản cũ vs phiên bản mới và đối chiếu output diff.
10. **Bash script có người can thiệp (HITL script):** Phương án cuối cùng nếu bắt buộc người phải click tay.

### Thắt chặt vòng lặp (Tighten the loop):

- Có thể làm cho nó chạy nhanh hơn không? (Cache setup, bỏ qua init thừa, thu hẹp phạm vi test).
- Tín hiệu có sắc nét hơn không? (Assert đúng triệu chứng cụ thể, không chỉ đơn thuần là "không bị crash").
- Có thể biến nó thành xác định (deterministic) 100% không? (Cố định time, seed RNG, đóng băng network).

Một vòng lặp mất 30 giây chập chờn (flaky) chẳng hơn gì không có vòng lặp; một vòng lặp chạy trong **2 giây mang tính tất định** chính là siêu năng lực gỡ lỗi.

### Tiêu chí hoàn thành Giai đoạn 1:
Giai đoạn 1 chỉ hoàn thành khi bạn có **MỘT CÂU LỆNH DUY NHẤT** (một file script, lệnh chạy test, hoặc lệnh curl) mà bạn đã chạy thử ít nhất một lần, và nó:
- [ ] **Có khả năng báo Đỏ (Red-capable):** Kích hoạt đúng đoạn code lỗi và khẳng định chính xác triệu chứng lỗi mà người dùng phản ánh.
- [ ] **Tất định (Deterministic):** Cho cùng một kết quả ở mỗi lần chạy.
- [ ] **Nhanh:** Tính bằng giây, không phải phút.
- [ ] **Agent tự chạy được:** Có thể chạy tự động không cần người giám sát.

*Nếu bạn thấy mình đang đọc code để đoán mò nguyên nhân trước khi có lệnh này, **DỪNG LẠI NGAY: Không có lệnh báo Đỏ thì tuyệt đối không sang Giai đoạn 2.***

---

## Giai đoạn 2: Tái hiện & Thu nhỏ ca lỗi (Repro & Minimize)

Chạy vòng lặp và quan sát nó chuyển sang màu ĐỎ khi lỗi xảy ra.

Xác nhận:
- [ ] Vòng lặp tái hiện đúng lỗi mà **người dùng** mô tả, không phải một lỗi khác xảy ra tình cờ gần đó. (Bắt sai bệnh $\rightarrow$ Chữa sai thuốc).
- [ ] Đã bắt được chính xác triệu chứng (thông báo lỗi, output sai, thời gian chậm).

### Thu nhỏ ca lỗi (Minimize):
Khi đã có ca tái hiện báo Đỏ, hãy gọt tỉa nó thành **kịch bản nhỏ nhất có thể mà vẫn báo Đỏ**. Cắt bỏ input thừa, các bước rườm rà, cấu hình không liên quan **từng bước một**, chạy lại vòng lặp sau mỗi lần cắt.

Hoàn thành khi: **Mọi phần tử còn lại đều là cốt yếu cho lỗi** — chỉ cần bỏ bất kỳ phần tử nào thì vòng lặp sẽ chuyển sang Xanh.

---

## Giai đoạn 3: Đưa ra Giả thuyết (Hypotheses)

Lập danh sách **3–5 giả thuyết có xếp hạng ưu tiên** trước khi bắt tay vào thử nghiệm bất kỳ giả thuyết nào.

Mỗi giả thuyết phải **có khả năng bác bỏ được (falsifiable)** theo mẫu:
> *"Nếu <X> là nguyên nhân, thì việc <thay đổi Y> sẽ làm lỗi biến mất / <thay đổi Z> sẽ làm lỗi nặng hơn."*

Nếu không thể đưa ra một dự đoán kiểm chứng cụ thể, giả thuyết đó chỉ là cảm tính (vibe): hãy gạt bỏ hoặc làm sắc bén nó lại.

Hiển thị danh sách giả thuyết cho người dùng trước khi thử nghiệm. Người dùng có kiến thức nghiệp vụ có thể xếp hạng lại ngay hoặc loại trừ những thứ họ đã biết.

---

## Giai đoạn 4: Đặt công cụ đo lường & Thử nghiệm (Instrumentation)

Mỗi lần thăm dò chỉ phục vụ một dự đoán cụ thể từ Giai đoạn 3. **MỖI LẦN CHỈ THAY ĐỔI MỘT BIẾN DUY NHẤT.**

Ưu tiên công cụ:
1. **Debugger / Breakpoints:** Một breakpoint đáng giá hơn 10 dòng log.
2. **Log có chủ đích:** Đặt log tại các ranh giới giúp phân biệt giữa các giả thuyết. Tuyệt đối không log vô tội vạ khắp nơi rồi grep.
3. **Gắn tiền tố cho mọi log debug:** Ví dụ `[DEBUG-a4f2]`. Việc dọn dẹp sau này chỉ tốn một lệnh grep duy nhất.
4. **Đối với lỗi suy giảm hiệu năng:** Đo lường cơ sở (profiler, `performance.now()`, query plan) trước khi sửa. Luôn đo trước, sửa sau.

---

## Giai đoạn 5: Sửa lỗi & Viết Regression Test

Viết bài kiểm thử hồi quy (regression test) **TRƯỚC KHI SỬA CODE**, nhưng chỉ khi có **khớp nối (seam) chuẩn xác** cho nó.

Khớp nối chuẩn xác là nơi bài test thực sự kiểm chứng lại mẫu lỗi đã xảy ra. Nếu khớp nối quá nông, bài test sẽ tạo ra sự tin tưởng giả tạo.

Quy trình:
1. Chuyển ca tái hiện đã thu nhỏ ở Giai đoạn 2 thành một bài test thất bại tại seam đó.
2. Quan sát bài test Báo Đỏ (Fail).
3. Viết code sửa lỗi tối thiểu.
4. Quan sát bài test Chuyển Xanh (Pass).
5. Chạy lại vòng lặp phản hồi ở Giai đoạn 1 với kịch bản đầy đủ ban đầu để đảm bảo lỗi đã biến mất hoàn toàn.

---

## Giai đoạn 6: Dọn dẹp (Clean up)

Trước khi tuyên bố hoàn thành:
- [ ] Kịch bản lỗi gốc ban đầu không còn tái hiện nữa (chạy lại vòng lặp Giai đoạn 1).
- [ ] Bài test hồi quy mới đã vượt qua (Pass).
- [ ] Mọi dòng log debug `[DEBUG-...]` đã được xóa sạch.
- [ ] Xóa các đoạn code/harness nháp tạm thời.
- [ ] Nêu rõ giả thuyết đúng trong commit message hoặc PR để người sau có thể học hỏi.
