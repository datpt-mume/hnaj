# Discovery metadata API

## `GET /api/meta/discovery`

Trả về dữ liệu taxonomy đang active để frontend dựng bộ lọc discovery. Endpoint public, read-only và không yêu cầu bearer token.

### Success response

```json
{
  "success": true,
  "message": "Discovery metadata loaded successfully.",
  "data": {
    "categories": [
      { "id": 59, "name": "Ăn uống", "slug": "an-uong" }
    ],
    "districts": [
      { "id": 183, "name": "Ba Đình", "code": null }
    ],
    "tags": [
      { "id": 171, "name": "Chill", "slug": "chill" }
    ]
  }
}
```

### Data rules

- Chỉ trả category, district và tag có `status = active`.
- Category và tag bị soft-delete không xuất hiện.
- Category, district và tag được sắp xếp theo `name` tăng dần.
- `code` của district có thể là `null`.
- Đây là metadata nhỏ, không phân trang.
- ID là ID runtime từ backend; frontend không được hard-code ID taxonomy.

### Error response

Lỗi dùng envelope chung theo [`docs/api-response-contract.md`](api-response-contract.md:1). Endpoint không nhận input nên không có validation payload.
