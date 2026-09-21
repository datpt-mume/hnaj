# Khi Nào Nên Mock (When to Mock)

Chỉ mock tại **ranh giới của hệ thống (system boundaries)**:

- Các API bên ngoài (cổng thanh toán, dịch vụ gửi email, SMS...).
- Cơ sở dữ liệu (đôi khi — nhưng ưu tiên dùng Test DB thực tế).
- Thời gian và tính ngẫu nhiên (Time / Randomness).
- Hệ thống tệp (File system — đôi khi).

**TUYỆT ĐỐI KHÔNG mock:**

- Các class / module do chính bạn viết.
- Các thành phần phối hợp nội bộ (internal collaborators).
- Bất kỳ thứ gì thuộc quyền kiểm soát của bạn trong codebase.

## Thiết kế code để dễ Mock (Designing for Mockability)

Tại ranh giới hệ thống, hãy thiết kế các giao diện sao cho dễ mock nhất:

**1. Áp dụng Dependency Injection (Tiêm phụ thuộc)**

Truyền các phụ thuộc bên ngoài vào từ tham số hàm, thay vì khởi tạo trực tiếp bên trong:

```typescript
// DỄ MOCK: Dependency được truyền vào từ ngoài
function processPayment(order, paymentClient) {
  return paymentClient.charge(order.total);
}

// KHÓ MOCK: Khởi tạo cứng bên trong hàm
function processPayment(order) {
  const client = new StripeClient(process.env.STRIPE_KEY);
  return client.charge(order.total);
}
```

**2. Ưu tiên giao diện kiểu SDK thay vì hàm fetch chung chung**

Tạo các hàm cụ thể cho từng thao tác bên ngoài thay vì một hàm fetch tổng quát với nhiều logic rẽ nhánh:

```typescript
// TỐT: Mỗi hàm có thể mock độc lập
const api = {
  getUser: (id) => fetch(`/users/${id}`),
  getOrders: (userId) => fetch(`/users/${userId}/orders`),
  createOrder: (data) => fetch('/orders', { method: 'POST', body: data }),
};

// XẤU: Việc mock đòi hỏi logic if/else phức tạp bên trong mock
const api = {
  fetch: (endpoint, options) => fetch(endpoint, options),
};
```

Lợi ích của phương pháp SDK:
- Mỗi mock chỉ cần trả về một cấu trúc dữ liệu cụ thể.
- Không cần logic điều kiện phức tạp trong phần setup bài test.
- Dễ dàng nhận biết bài test đang gọi đến endpoint nào.
- Đảm bảo an toàn kiểu dữ liệu (Type safety) cho từng endpoint.
