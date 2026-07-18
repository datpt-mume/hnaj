# API Documentation

Bộ tài liệu API của HNaj gồm:

- `openapi.yaml`: HTTP contract máy đọc, dùng để đồng bộ BE và FE.
- `recommendation-policy.md`: quy tắc lọc, xếp hạng, fallback và chống lặp.
- `CHANGELOG.md`: lịch sử thay đổi contract.

## Source of truth

- Nghiệp vụ: [`../PRD_TECH_SPEC.md`](../PRD_TECH_SPEC.md)
- HTTP contract: [`openapi.yaml`](openapi.yaml)
- Database schema: `docs/database/`; migration Laravel là nguồn thực thi.

## Quy tắc thay đổi

Mọi thay đổi client-observable phải cập nhật `openapi.yaml` trong cùng change, bao gồm route, method, status, request/response schema, auth, permission, error, pagination, idempotency và metadata nghiệp vụ. Cập nhật thêm policy khi recommendation thay đổi và `CHANGELOG.md` cho mọi thay đổi contract.

OpenAPI hiện là target contract trong giai đoạn tài liệu hóa. Các operation chỉ được xem là implemented sau khi route, test và code tương ứng tồn tại trong backend.

## Validation

Cần kiểm tra YAML syntax, operation IDs, `$ref`, security requirements và examples. Chưa cài validator mới trong phase tài liệu này; việc thêm package/tool phải được duyệt trước.
