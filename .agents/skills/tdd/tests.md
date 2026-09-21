# Bài Test Tốt và Bài Test Xấu (Good and Bad Tests)

## Bài Test Tốt (Good Tests)

**Kiểu tích hợp (Integration-style)**: Kiểm thử thông qua các giao diện thực tế, không mock các thành phần nội bộ.

```typescript
// TỐT: Kiểm tra hành vi quan sát được từ bên ngoài
test("người dùng có thể thanh toán với giỏ hàng hợp lệ", async () => {
  const cart = createCart();
  cart.add(product);
  const result = await checkout(cart, paymentMethod);
  expect(result.status).toBe("confirmed");
});
```

Đặc điểm nhận diện:

- Kiểm tra hành vi mà người dùng hoặc caller thực sự quan tâm.
- Chỉ sử dụng Public API.
- Sống sót qua các đợt tái cấu trúc mã nguồn nội bộ.
- Mô tả CÁI GÌ (WHAT), không mô tả LÀM NHƯ THẾ NÀO (HOW).
- Mỗi bài test chỉ có một khẳng định (assertion) logic duy nhất.

## Bài Test Xấu (Bad Tests)

**Bài test bám vào chi tiết triển khai (Implementation-detail tests)**: Bị gắn chặt vào cấu trúc nội bộ.

```typescript
// XẤU: Kiểm tra chi tiết triển khai nội bộ
test("checkout calls paymentService.process", async () => {
  const mockPayment = jest.mock(paymentService);
  await checkout(cart, payment);
  expect(mockPayment.process).toHaveBeenCalledWith(cart.total);
});
```

Dấu hiệu cảnh báo:

- Mock các thành phần phối hợp nội bộ (internal collaborators).
- Viết test cho các private method.
- Kiểm tra số lần gọi hàm hoặc thứ tự gọi hàm (`toHaveBeenCalledWith`, `toHaveBeenCalledTimes`).
- Bài test bị gãy khi tái cấu trúc mặc dù chức năng không hề thay đổi.
- Tên bài test mô tả CÁCH LÀM (HOW) thay vì KẾT QUẢ (WHAT).
- Kiểm tra qua phương thức gián tiếp bên ngoài thay vì qua giao diện chính.

```typescript
// XẤU: Bỏ qua interface để kiểm tra trực tiếp DB
test("createUser saves to database", async () => {
  await createUser({ name: "Alice" });
  const row = await db.query("SELECT * FROM users WHERE name = ?", ["Alice"]);
  expect(row).toBeDefined();
});

// TỐT: Kiểm tra thông qua interface
test("createUser makes user retrievable", async () => {
  const user = await createUser({ name: "Alice" });
  const retrieved = await getUser(user.id);
  expect(retrieved.name).toBe("Alice");
});
```

**Bài test tự chứng minh vô nghĩa (Tautological tests)**: Giá trị kỳ vọng lặp lại y hệt cách tính toán của code, dẫn đến việc test luôn luôn pass.

```typescript
// XẤU: Giá trị kỳ vọng được tính lại y hệt như code chính
test("calculateTotal sums line items", () => {
  const items = [{ price: 10 }, { price: 5 }];
  const expected = items.reduce((sum, i) => sum + i.price, 0);
  expect(calculateTotal(items)).toBe(expected);
});

// TỐT: Giá trị kỳ vọng là một hằng số độc lập, đã biết trước
test("calculateTotal sums line items", () => {
  expect(calculateTotal([{ price: 10 }, { price: 5 }])).toBe(15);
});
```
