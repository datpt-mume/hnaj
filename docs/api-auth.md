# API authentication — HNAJ

- **Trạng thái:** Đã triển khai
- **Cập nhật:** 2026-08-02
- **Cơ chế:** Laravel Sanctum personal access token, gửi bằng `Authorization: Bearer <token>`
- **Envelope:** Tuân theo [`docs/api-response-contract.md`](api-response-contract.md)

## 1. Role và luồng đăng nhập

| Role | Endpoint đăng nhập | Google OAuth | Phạm vi |
|---|---|---:|---|
| `user` | `POST /api/auth/login` | Có | Người dùng thông thường |
| `sub_admin` | `POST /api/auth/login` | Không | Quản lý place được cấp quyền; email chỉ nhận thông báo/activation |
| `admin` | `POST /api/admin/auth/login` | Không | Khu vực quản trị |

Endpoint user cấp token cho tài khoản có role `user` **hoặc** `sub_admin`; Sub-admin dùng cùng token user, login trang thường hoạt động như user và chỉ được thêm quyền vào khu quản lý place. Endpoint admin chỉ cấp token cho tài khoản có role `admin`. Backend luôn đọc role từ database; frontend guard không phải security boundary.

## 2. Đăng ký user

### `POST /api/auth/register`

```json
{
  "username": "minh.anh",
  "full_name": "Nguyễn Minh Anh",
  "email": "minh.anh@example.com",
  "password": "matkhau2026",
  "password_confirmation": "matkhau2026"
}
```

- `username`: 3–50 ký tự, chỉ chữ thường, số, dấu chấm và gạch dưới; không bắt đầu/kết thúc bằng dấu chấm hoặc gạch dưới.
- `email`: duy nhất, được chuẩn hóa chữ thường.
- `password`: tối thiểu 8 ký tự, có chữ và số.
- Response HTTP `201`; không trả access token trước khi email được xác thực.

## 3. Xác thực email

### `POST /api/auth/email/verify`

```json
{ "token": "plaintext-token-from-email" }
```

Token dùng một lần, hiệu lực 24 giờ. Database chỉ lưu SHA-256 hash của token. Liên kết email chuyển người dùng tới `${FRONTEND_URL}/verify-email?token=...`. Nếu token vẫn hợp lệ nhưng account đã được xác thực qua luồng khác, API trả HTTP `409` với code `EMAIL_ALREADY_VERIFIED` và không tiêu thụ token đó.

### `POST /api/auth/email/resend`

```json
{ "email": "minh.anh@example.com" }
```

Response luôn trung lập để không tiết lộ email có tồn tại, đã xác thực hay đang bị khóa. Chỉ tài khoản active và chưa xác thực mới được phát hành mail mới; khi phát hành token mới, token chưa dùng trước đó bị vô hiệu hóa.

## 4. Đăng nhập user

### `POST /api/auth/login`

```json
{
  "username": "minh.anh",
  "password": "matkhau2026"
}
```

Yêu cầu tài khoản active, email đã xác thực và có role `user` hoặc `sub_admin`. Sub-admin được tạo thủ công chưa thể đăng nhập cho tới khi hoàn tất setup tài khoản (mục 5).

```json
{
  "success": true,
  "message": "Signed in successfully.",
  "data": {
    "user": {
      "id": 1,
      "username": "minh.anh",
      "full_name": "Nguyễn Minh Anh",
      "email": "minh.anh@example.com",
      "avatar_url": null,
      "status": "active",
      "email_verified": true,
      "roles": ["user"]
    },
    "token": "sanctum-plain-text-token"
  }
}
```

## 5. Kích hoạt tài khoản Sub-admin

### `POST /api/auth/account/setup`

```json
{
  "token": "plaintext-token-from-email",
  "password": "matkhau2026",
  "password_confirmation": "matkhau2026"
}
```

- Chỉ áp dụng cho tài khoản Sub-admin được Admin tạo thủ công.
- Token dùng một lần, hiệu lực 24 giờ, lưu SHA-256 hash trong `account_setup_tokens`. Liên kết email chuyển tới `${FRONTEND_URL}/setup-account?token=...`.
- Kích hoạt đồng thời: đặt password mới, đánh dấu email verified (email xác nhận quyền sở hữu) và account mới có thể đăng nhập.
- Token sai/đã dùng/hết hạn trả `422` code `INVALID_VERIFICATION_TOKEN`.

## 6. Google OAuth cho user

1. `GET /api/auth/google/redirect` trả `data.authorization_url` có OAuth `state` chống CSRF và set cookie HttpOnly `hnaj_google_oauth_flow` trong 5 phút.
2. Trình duyệt chuyển tới Google rồi quay về `GET /api/auth/google/callback`.
3. Backend redirect tới `${FRONTEND_URL}/auth/google/callback?code=...` bằng exchange code một lần, hiệu lực 60 giây. Nếu người dùng hủy hoặc Google trả provider error, backend redirect về `${FRONTEND_URL}/auth/google/callback?error=GOOGLE_AUTH_FAILED` và xóa flow cookie.
4. Frontend gọi `POST /api/auth/google/exchange` với `{ "code": "..." }` và cùng cookie HttpOnly để nhận user và Sanctum token.

Google phải xác nhận email đã verified. Tài khoản Google mới nhận role `user`; username có dạng `{local-part}_{mã-ngẫu-nhiên-6-ký-tự}` để tránh trùng lặp. Khi cực hiếm trường hợp unique constraint vẫn bị vi phạm, hệ thống tự retry với username mới; các lỗi database khác không bị che giấu. Google OAuth không liên kết tài khoản admin/sub-admin và không thay thế một Google identity đã liên kết trước đó. Trước lúc phát bearer token, exchange luôn tải trạng thái account hiện tại và kiểm tra lại role `user`. Cookie OAuth chỉ ràng buộc browser khởi tạo flow; payload public của `/google/exchange` vẫn là `{ "code": "..." }`.

Mọi callback Google không hợp lệ (thiếu/sai `state`, thiếu cả `code` lẫn `error`, gửi đồng thời cả hai, v.v.) đều redirect trình duyệt về `${FRONTEND_URL}/auth/google/callback?error=GOOGLE_AUTH_FAILED` và xóa cookie OAuth tạm, thay vì trả trang JSON 422 cho trình duyệt.

## 7. Admin login và tạo tài khoản

### `POST /api/admin/auth/login`

Payload giống user login nhưng chỉ chấp nhận role `admin`.

Admin không được seed credential. Tạo tài khoản admin hệ thống duy nhất qua Tinker, **chỉ được chạy một lần duy nhất**:

```php
app(\App\Actions\Auth\CreateAdminAccount::class)->handle(
    username: 'admin.username',
    fullName: 'System Administrator',
    email: 'admin@example.com',
    password: 'replace-with-a-strong-password',
);
```

Action là bootstrap one-time create-only: từ chối nếu hệ thống đã có admin, hoặc username/email đã thuộc bất kỳ tài khoản nào. Không có nhánh cập nhật account hiện có; mọi lần chạy sau lần đầu đều bị từ chối.

Không ghi password thật vào source code, tài liệu, shell history chia sẻ hoặc log.

## 8. Cập nhật profile

### `PATCH /api/auth/me`

Cập nhật `full_name` của tài khoản đang đăng nhập. Yêu cầu Sanctum token và role `user` hoặc `sub_admin`.

```json
{
  "full_name": "Nguyễn Minh Anh Mới"
}
```

- `full_name`: bắt buộc, string, tối đa 255 ký tự, được trim hai đầu.
- Các trường `username`, `email`, `avatar_url` là read-only và không bị thay đổi bởi request này kể cả khi gửi kèm.

Response HTTP `200`:

```json
{
  "success": true,
  "message": "Profile updated successfully.",
  "data": {
    "user": {
      "id": 1,
      "username": "minh.anh",
      "full_name": "Nguyễn Minh Anh Mới",
      "email": "minh.anh@example.com",
      "avatar_url": null,
      "status": "active",
      "email_verified": true,
      "roles": ["user"]
    }
  }
}
```

## 9. Endpoint cần token

- `GET /api/auth/me`: thông tin user hiện tại, yêu cầu Sanctum token và role `user` hoặc `sub_admin`.
- `PATCH /api/auth/me`: cập nhật `full_name` của user hiện tại, yêu cầu Sanctum token và role `user` hoặc `sub_admin`.
- `POST /api/auth/logout`: thu hồi token đang dùng.
- `GET /api/admin/auth/me`: yêu cầu Sanctum token và role `admin`.
- `POST /api/admin/auth/logout`: yêu cầu Sanctum token và role `admin`, thu hồi token admin đang dùng.

## 10. Mã lỗi auth

| Code | HTTP | Ý nghĩa |
|---|---:|---|
| `INVALID_CREDENTIALS` | 401 | Sai username hoặc password |
| `EMAIL_NOT_VERIFIED` | 403 | Email chưa xác thực |
| `ACCOUNT_NOT_ACTIVE` | 403 | Tài khoản không active |
| `FORBIDDEN_ROLE` | 403 | Sai role đối với endpoint |
| `UNAUTHENTICATED` | 401 | Thiếu hoặc sai bearer token |
| `INVALID_VERIFICATION_TOKEN` | 422 | Token email sai, hết hạn hoặc đã dùng |
| `EMAIL_ALREADY_VERIFIED` | 409 | Email đã xác thực; không dùng cho resend vì resend luôn trung lập |
| `GOOGLE_AUTH_FAILED` | 422 | Không hoàn tất được Google OAuth |

Các endpoint public có rate limit tại route để giảm brute force và abuse.
