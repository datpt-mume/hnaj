# Định dạng báo cáo HTML

Đánh giá kiến ​​trúc được hiển thị dưới dạng một tệp HTML độc lập trong thư mục tạm thời của hệ điều hành. Tailwind và Nàng tiên cá đều đến từ CDN. Nàng tiên cá xử lý các sơ đồ dạng đồ thị một cách đáng tin cậy; các div được xây dựng bằng tay và SVG nội tuyến xử lý nhiều hình ảnh biên tập hơn (sơ đồ khối, mặt cắt). Kết hợp cả hai: đừng dựa vào Nàng tiên cá trong mọi việc, nó sẽ bắt đầu có vẻ chung chung.

## Giàn giáo

```html
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Architecture review for {{repo name}}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module">
      import mermaid from "https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs";
      mermaid.initialize({ startOnLoad: true, theme: "neutral", securityLevel: "loose" });
    </script>
    <style>
      /* small custom layer for things Tailwind doesn't cover cleanly:
         dashed seam lines, hand-drawn-feeling arrow heads, etc. */
      .seam { stroke-dasharray: 4 4; }
      .leak { stroke: #dc2626; }
      .deep { background: linear-gradient(135deg, #0f172a, #1e293b); }
    </style>
  </head>
  <body class="bg-stone-50 text-slate-900 font-sans">
    <main class="max-w-5xl mx-auto px-6 py-12 space-y-12">
      <header>...</header>
      <section id="candidates" class="space-y-10">...</section>
      <section id="top-recommendation">...</section>
    </main>
  </body>
</html>
```

## tiêu đề

Tên repo, ngày tháng và chú giải nhỏ gọn: hộp đặc = mô-đun, đường đứt nét = khớp nối (seam), mũi tên đỏ = rò rỉ, hộp tối dày = mô-đun sâu. Không có đoạn giới thiệu. Đi thẳng vào ứng viên.

## Thẻ ứng viên

Các sơ đồ mang trọng lượng. Văn xuôi thưa thớt, đơn giản và sử dụng các thuật ngữ chú giải (từ kỹ năng `/codebase-design`) mà không trang trọng.

Mỗi ứng cử viên là một `<article>`:

- **Tiêu đề**: ngắn gọn, đặt tên cho phần đào sâu (ví dụ: "Thu gọn quy trình tiếp nhận Đơn hàng").
- **Hàng huy hiệu**: cường độ đề xuất (`Strong` = ngọc lục bảo, `Worth exploring` = hổ phách, `Speculative` = đá phiến), cùng với thẻ cho danh mục phụ thuộc (`in-process`, `local-substitutable`, `ports & adapters`, `mock`).
- **Tệp**: danh sách đơn cách, `font-mono text-sm`.
- **Biểu đồ Trước / Sau**: phần trung tâm. Hai cột cạnh nhau. Xem các mẫu dưới đây.
- **Vấn đề**: một câu. Điều gì đau đớn.
- **Giải pháp**: một câu. Những gì thay đổi.
- **Thắng**: dấu đầu dòng, mỗi dấu đầu dòng 6 từ. ví dụ. "Thử nghiệm đạt được một giao diện", "Logic định giá ngừng rò rỉ", "Xóa 4 trình bao bọc nông".
- **Chú thích ADR** (nếu có): một dòng trong hộp màu hổ phách.

Không có đoạn giải thích. Nếu sơ đồ cần một đoạn văn để hiểu, hãy vẽ lại sơ đồ.

## Sơ đồ mẫu

Chọn mẫu phù hợp với ứng viên. Trộn chúng. Đừng làm cho mọi sơ đồ trông giống nhau. Sự đa dạng là một phần của điểm.

### Biểu đồ nàng tiên cá (công cụ dành cho phần phụ thuộc/luồng cuộc gọi)

Sử dụng Nàng tiên cá `flowchart` hoặc `graph` khi vấn đề là "X gọi Y gọi Z và nhìn đống hỗn độn này xem". Bọc nó trong một thẻ theo phong cách Tailwind để nó không có cảm giác như bị rơi vào. Tạo kiểu với classDef để tô màu các cạnh rò rỉ màu đỏ và mô-đun sâu sẽ tối. Sơ đồ trình tự hoạt động tốt cho "trước: 6 chuyến khứ hồi; sau: 1".

```html
<div class="rounded-lg border border-slate-200 bg-white p-4">
  <pre class="mermaid">
    flowchart LR
      A[OrderHandler] --> B[OrderValidator]
      B --> C[OrderRepo]
      C -.leak.-> D[PricingClient]
      classDef leak stroke:#dc2626,stroke-width:2px;
      class C,D leak
  </pre>
</div>
```

### Hộp và mũi tên được chế tạo bằng tay (khi bố cục của Nàng tiên cá chiến đấu với bạn)

Mô-đun dưới dạng `<div>` có đường viền và nhãn. Mũi tên dưới dạng phần tử SVG `<line>` hoặc `<path>` nội tuyến được định vị tuyệt đối trên một vùng chứa tương đối. Hãy đạt được điều này khi bạn muốn sơ đồ "sau" có cảm giác giống như một mô-đun sâu có viền dày với các phần bên trong có màu xám, vì Nàng tiên cá sẽ không hiển thị biểu đồ đó với trọng lượng phù hợp.

### Mặt cắt ngang (tốt cho độ nông phân lớp)

Xếp chồng các dải ngang (`h-12 border-l-4`) để hiển thị các lớp mà cuộc gọi đi qua. Trước: 6 lớp mỏng, mỗi lớp không làm gì cả. Sau: 1 dải dày có dán nhãn trách nhiệm tổng hợp.

### Sơ đồ khối (tốt cho "giao diện rộng như triển khai")

Hai hình chữ nhật trên mỗi mô-đun: một dành cho diện tích bề mặt giao diện, một dành cho việc triển khai. Trước: hình chữ nhật giao diện có chiều cao gần bằng hình chữ nhật triển khai (nông). Sau: hình chữ nhật giao diện ngắn, hình chữ nhật triển khai cao (sâu).

### Thu gọn biểu đồ cuộc gọi

Trước: một cây lệnh gọi hàm được hiển thị dưới dạng các hộp lồng nhau. Sau: cùng một cái cây bị thu gọn vào một hộp, với các lệnh gọi nội bộ hiện đã mờ dần bên trong nó.

## Hướng dẫn phong cách

- Biên tập tinh gọn, không phải bảng điều khiển của công ty. Khoảng trắng rộng rãi. Serif tùy chọn cho tiêu đề (`font-serif` hoạt động tốt với đá/đá phiến).
- Màu sắc vừa phải: một điểm nhấn (ngọc lục bảo hoặc chàm) cộng với màu đỏ thể hiện sự rò rỉ và màu hổ phách để cảnh báo.
- Giữ sơ đồ có chiều cao ~320px để phần trước/sau nằm thoải mái cạnh nhau mà không cần cuộn.
- Sử dụng `text-xs uppercase tracking-wider` cho nhãn mô-đun bên trong sơ đồ để chúng đọc dưới dạng sơ đồ chứ không phải giao diện người dùng.
- Các tập lệnh duy nhất là Tailwind CDN và nhập ESM của Nàng tiên cá. Mặt khác, báo cáo ở dạng tĩnh: không có mã ứng dụng, không có tính tương tác ngoài kết xuất của chính Nàng tiên cá.

## Phần đề xuất hàng đầu

Một thẻ lớn hơn. Tên ứng viên, một câu về lý do tại sao, gắn liên kết vào thẻ của ứng viên đó. Thế thôi.

## Giai điệu

Tiếng Anh đơn giản, ngắn gọn nhưng các danh từ và động từ kiến ​​trúc lại xuất phát trực tiếp từ kỹ năng `/codebase-design`. Sự quyết định không phải là cái cớ để trôi dạt.

**Sử dụng chính xác:** mô-đun, giao diện, cách triển khai, độ sâu, độ sâu, độ nông, khớp nối (seam), bộ chuyển đổi, đòn bẩy, vị trí.

**Không bao giờ thay thế:** thành phần, dịch vụ, đơn vị (đối với mô-đun) · API, chữ ký (đối với giao diện) · ranh giới (đối với khớp nối (seam)) · lớp, trình bao bọc (đối với mô-đun, khi bạn muốn nói đến mô-đun).

**Cụm từ phù hợp với văn phong:**

- "Module tiếp nhận đơn hàng còn nông: giao diện gần như phù hợp với cách triển khai."
- "Giá rò rỉ khắp khớp nối (seam)."
- "Đào sâu: một giao diện, một nơi để thử nghiệm."
- "Hai bộ điều hợp biện minh cho khớp nối (seam): HTTP trong sản phẩm, trong bộ nhớ trong các thử nghiệm."

**Giành được đạn** đặt tên cho mức tăng theo thuật ngữ thuật ngữ: *"địa điểm: lỗi tập trung trong một mô-đun"*, *"đòn bẩy: một giao diện, N trang web gọi"*, *"giao diện co lại; quá trình triển khai hấp thụ các trình bao bọc"*. Đừng viết *"dễ bảo trì hơn"* hoặc *"mã sạch hơn"*, vì những thuật ngữ đó không có trong bảng thuật ngữ và không có vị trí của chúng.

Không phòng ngừa rủi ro, không hắng giọng, không "điều đáng chú ý là...". Nếu một câu có thể là một viên đạn, hãy biến nó thành một viên đạn. Nếu một viên đạn có thể cắt được, hãy cắt nó. Nếu một thuật ngữ không có trong bảng thuật ngữ `/codebase-design`, hãy tìm thuật ngữ đó trước khi phát minh ra thuật ngữ mới.
