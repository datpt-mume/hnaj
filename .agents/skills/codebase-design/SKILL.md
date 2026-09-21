---
name: codebase-design
description: "Bộ từ vựng và nguyên tắc dùng chung để thiết kế Module sâu (Deep Modules). Sử dụng khi người dùng muốn thiết kế hoặc cải thiện giao diện của module, tìm cơ hội làm sâu module, quyết định vị trí đặt khớp nối (seam), hoặc làm cho mã nguồn dễ kiểm thử hơn và dễ điều hướng cho AI."
---

# Thiết Kế Codebase (Codebase Design)

Thiết kế các **module sâu (deep modules)**: ẩn chứa nhiều hành vi phong phú đằng sau một giao diện (interface) nhỏ gọn, được đặt tại một khớp nối (seam) rõ ràng, và có thể kiểm thử toàn diện thông qua chính giao diện đó. Áp dụng ngôn ngữ và các nguyên lý này ở bất cứ nơi nào mã nguồn đang được thiết kế hoặc tái cấu trúc. Mục tiêu là mang lại **đòn bẩy (leverage)** cho người gọi, **tính cục bộ (locality)** cho người bảo trì, và **khả năng kiểm thử (testability)** cho tất cả mọi người.

## Bảng thuật ngữ chuẩn (Glossary)

Sử dụng chính xác các thuật ngữ này, không thay thế bằng "component", "service", "API" hay "boundary". Tính nhất quán trong từ vựng là điều cốt lõi:

- **Module**: Bất cứ thứ gì có một giao diện (interface) và một phần triển khai (implementation). Cố ý không phân biệt quy mô: có thể là một hàm, một class, một package, hoặc một lát cắt xuyên suốt các tầng. *Tránh dùng*: unit, component, service.
- **Interface (Giao diện)**: Tất cả những gì caller cần biết để sử dụng module một cách chính xác: chữ ký kiểu dữ liệu (type signature), các bất biến (invariants), ràng buộc về thứ tự gọi, các chế độ lỗi, cấu hình bắt buộc và đặc tính hiệu năng. *Tránh dùng*: API, signature (quá hẹp, chỉ ám chỉ bề mặt kiểu dữ liệu).
- **Implementation (Phần triển khai)**: Những gì nằm bên trong module, thân mã nguồn của nó. Phân biệt với **Adapter**: một đối tượng có thể là một adapter nhỏ với phần triển khai lớn (như một Postgres repo) hoặc một adapter lớn với phần triển khai nhỏ (như một fake in-memory). Dùng "adapter" khi đang bàn về khớp nối (seam); dùng "implementation" trong các trường hợp còn lại.
- **Depth (Độ sâu)**: Đòn bẩy tại giao diện. Lượng hành vi mà một caller (hoặc bài test) có thể kích hoạt trên mỗi đơn vị giao diện mà họ phải học. Một module là **sâu (deep)** khi một lượng lớn hành vi nằm sau một interface nhỏ gọn; là **nông (shallow)** khi interface phức tạp gần bằng chính phần triển khai bên trong.
- **Seam (Khớp nối)** *(Michael Feathers)*: Một vị trí nơi bạn có thể thay đổi hành vi của hệ thống mà không cần chỉnh sửa mã nguồn tại chính vị trí đó; là *vị trí vật lý* nơi interface của module tồn tại. Việc quyết định đặt seam ở đâu là một quyết định thiết kế độc lập với những gì nằm sau nó. *Tránh dùng*: boundary (dễ nhầm với bounded context của DDD).
- **Adapter (Bộ chuyển đổi)**: Một thực thể cụ thể đáp ứng interface tại một seam. Mô tả *vai trò* (nó lấp vào vị trí nào), không phải nội dung bên trong.
- **Leverage (Đòn bẩy)**: Giá trị mà callers nhận được từ độ sâu. Nhiều năng lực hơn trên mỗi đơn vị interface phải học. Một lần viết phần triển khai mang lại lợi ích cho N vị trí gọi hàm và M bài test.
- **Locality (Tính cục bộ)**: Giá trị mà người bảo trì nhận được từ độ sâu. Các thay đổi, lỗi, tri thức và quy trình kiểm chứng tập trung tại một nơi duy nhất thay vì rải rác khắp các caller. Sửa một nơi là sửa cho toàn bộ hệ thống.

## Module Sâu vs Module Nông (Deep vs Shallow)

**Module Sâu (Deep module)** = Interface nhỏ gọn + Phần triển khai phong phú (NÊN DÙNG):

```
┌─────────────────────────┐
│     Interface Nhỏ       │  ← Ít phương thức, tham số đơn giản
├─────────────────────────┤
│                         │
│  Triển Khai Phong Phú   │  ← Ẩn toàn bộ logic phức tạp bên trong
│                         │
└─────────────────────────┘
```

**Module Nông (Shallow module)** = Interface cồng kềnh + Phần triển khai sơ sài (NÊN TRÁNH):

```
┌─────────────────────────────────────┐
│          Interface Lớn              │  ← Quá nhiều method, tham số phức tạp
├─────────────────────────────────────┤
│  Triển Khai Mỏng (Chỉ chuyển tiếp)  │  ← Hầu như chỉ pass-through dữ liệu
└─────────────────────────────────────┘
```

Khi thiết kế một interface, hãy tự hỏi:
- Tôi có thể giảm bớt số lượng phương thức không?
- Tôi có thể đơn giản hóa các tham số truyền vào không?
- Tôi có thể giấu thêm sự phức tạp vào bên trong không?

## Các nguyên lý cốt lõi (Principles)

- **Độ sâu là thuộc tính của Interface, không phải của Implementation:** Một module sâu có thể được cấu thành nội bộ từ nhiều thành phần nhỏ, có thể mock và hoán đổi cho nhau; chúng chỉ đơn giản không phải là một phần của interface công khai. Một module có thể có các **khớp nối nội bộ (internal seams)** (dành riêng cho việc test nội bộ của nó) bên cạnh **khớp nối bên ngoài (external seam)** tại interface của nó.
- **Phép thử Xóa bỏ (The deletion test):** Hãy tưởng tượng việc xóa module này đi. Nếu sự phức tạp biến mất hoàn toàn, nó chỉ là một module chuyển tiếp (pass-through). Nếu sự phức tạp tái xuất hiện và phân tán ra khắp N callers, module này đã thực sự làm tròn bổn phận của nó.
- **Interface chính là bề mặt kiểm thử (The interface is the test surface):** Caller và bài test cùng đi qua một seam duy nhất. Nếu bạn muốn test *xuyên qua* interface vào ruột bên trong, module đó có thể đang có hình dạng sai.
- **Một adapter nghĩa là seam giả định; hai adapter mới là seam thực sự:** Đừng tạo ra một seam trừ khi thực sự có thứ gì đó biến đổi qua nó (thường là môi trường Production + môi trường Test). Một seam chỉ có 1 adapter duy nhất chỉ là sự gián tiếp (indirection) thừa thãi.

## Thiết kế để dễ kiểm thử (Designing for testability)

Giao diện tốt giúp việc kiểm thử diễn ra tự nhiên:

1. **Nhận phụ thuộc vào, không tự khởi tạo phụ thuộc:**
   ```typescript
   // Dễ test
   function processOrder(order, paymentGateway) {}

   // Khó test
   function processOrder(order) {
     const gateway = new StripeGateway();
   }
   ```

2. **Trả về kết quả, hạn chế tạo tác dụng phụ (side effects):**
   ```typescript
   // Dễ test
   function calculateDiscount(cart): Discount {}

   // Khó test
   function applyDiscount(cart): void {
     cart.total -= discount;
   }
   ```

3. **Thu hẹp diện tích bề mặt (Small surface area):** Càng ít phương thức $\rightarrow$ càng ít bài test cần viết. Càng ít tham số $\rightarrow$ việc setup bài test càng đơn giản.

## Tài liệu mở rộng
- **Làm sâu một cụm module dựa trên các phụ thuộc của nó:** Xem [DEEPENING.md](DEEPENING.md).
- **Khám phá các phương án giao diện thay thế:** Xem [DESIGN-IT-TWICE.md](DESIGN-IT-TWICE.md).
