# API response contract

Tất cả endpoint dưới tiền tố `/api` phải trả về một trong hai envelope sau.

## Success response

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": {},
  "meta": {}
}
```

`meta` là tùy chọn và dùng cho pagination hoặc metadata bổ sung.

## Error response

```json
{
  "success": false,
  "message": "The request could not be completed.",
  "errors": {},
  "code": "ERROR_CODE"
}
```

`errors` và `code` là tùy chọn. API phải dùng HTTP status phù hợp; trường `success` phản ánh kết quả nghiệp vụ.

Các lỗi validation trả về HTTP `422` với code `VALIDATION_ERROR`; resource không tồn tại trả về HTTP `404` với code `NOT_FOUND`; lỗi không mong đợi trả về HTTP `500` với code `INTERNAL_SERVER_ERROR`.

## API test

`GET /api/test` xác nhận kết nối giữa frontend và backend.

```json
{
  "success": true,
  "message": "API connection is working.",
  "data": {
    "service": "hnaj-be",
    "status": "ok"
  }
}
```
