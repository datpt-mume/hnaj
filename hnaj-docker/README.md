# HNAJ Docker

Bộ Docker này cung cấp môi trường development cơ bản cho toàn hệ thống:

- Backend Laravel 12 trên PHP 8.3 + PHP-FPM.
- Nginx làm web server cho backend.
- Frontend React + Vite + TypeScript trên Node 24.
- MySQL 8.4 với named volume để giữ dữ liệu.

## Kiến trúc dễ hiểu

- **Image:** khuôn đóng gói hệ điều hành, runtime và dependency. Image được tạo từ Dockerfile.
- **Container:** tiến trình đang chạy từ một image. Có thể dừng và tạo lại mà không mất dữ liệu trong volume.
- **Bind mount:** ánh xạ source trên máy vào container. Vì vậy sửa code trong `hnaj-be/` hoặc `hnaj-fe/` sẽ được container nhìn thấy mà thường không cần build lại.
- **Named volume:** vùng lưu trữ do Docker quản lý. `mysql_data_v2` giữ dữ liệu MySQL; `backend_vendor`, `backend_storage` và `frontend_node_modules` giữ dependency/file runtime cần thiết qua lần restart.
- **Network:** mạng nội bộ `hnaj` cho phép service gọi nhau bằng tên service, ví dụ backend dùng host database là `mysql`.
- **Healthcheck:** kiểm tra MySQL thực sự nhận kết nối trước khi backend khởi động.

## Cổng truy cập

| Thành phần | Địa chỉ từ máy host | Ghi chú |
|---|---|---|
| Frontend Vite | http://localhost:8082 | Hot reload, cổng trong container là 5173; cổng 8080 đang được phpMyAdmin của môi trường khác sử dụng |
| Backend Laravel | http://localhost:8081 | Nginx chuyển PHP request tới PHP-FPM |
| Backend health | http://localhost:8081/health | Trả về `ok` |
| MySQL | `127.0.0.1:3307` | Chỉ mở trên localhost, không public ra mạng; cổng 3306 đang được dịch vụ khác sử dụng |

Trong Docker, backend kết nối MySQL bằng `mysql:3306`. Từ công cụ chạy trên máy host, dùng `127.0.0.1:3307` với database/user/password trong file `.env`.

## Khởi tạo lần đầu

Chạy từ thư mục gốc repository:

```bash
cp hnaj-docker/.env.example hnaj-docker/.env
cd hnaj-docker
docker compose --env-file .env config --quiet
docker compose --env-file .env build
docker compose --env-file .env up -d --remove-orphans
```

Source Laravel và React + Vite đã được khởi tạo trong `hnaj-be/` và `hnaj-fe/`. Nếu phải khởi tạo lại từ đầu, dependency cần được tạo bằng Composer/npm theo đúng version baseline trước khi build image.

Sinh key và chạy migration mặc định:

```bash
docker compose --env-file .env exec backend php artisan key:generate --force
docker compose --env-file .env exec backend php artisan migrate --force
docker compose --env-file .env exec backend php artisan migrate:status
```

`key:generate` tạo khóa mã hóa/signing cho Laravel. Migration tạo bảng mặc định như users, cache và jobs; không xóa bảng hiện có.

## Các lệnh thường dùng

```bash
# Xem trạng thái và log
docker compose --env-file .env ps
docker compose --env-file .env logs -f backend backend-web frontend mysql

# Dừng container nhưng giữ volume
docker compose --env-file .env stop

# Tạo lại container theo cấu hình hiện tại, giữ volume
docker compose --env-file .env up -d --remove-orphans

# Rebuild khi đổi Dockerfile, PHP extension, Node package hoặc lockfile
docker compose --env-file .env build --no-cache
docker compose --env-file .env up -d

# Chạy kiểm tra Laravel
docker compose --env-file .env exec backend php artisan test

# Chạy kiểm tra frontend
docker compose --env-file .env exec frontend npm run lint
docker compose --env-file .env exec frontend npm run build
```

## Khi nào cần rebuild?

- **Không cần rebuild:** sửa PHP/React source, route, controller, component hoặc CSS khi đang bind mount development.
- **Cần rebuild backend:** đổi `hnaj-docker/backend/Dockerfile`, PHP extension, package hệ thống, `composer.json` hoặc `composer.lock`.
- **Cần rebuild frontend:** đổi `hnaj-docker/frontend/Dockerfile`, `package.json` hoặc `package-lock.json`. Sau đó `npm ci` trong image sẽ được chạy lại.
- **Không cần rebuild vì đổi `.env`:** restart/recreate container để nhận biến môi trường mới; không commit `.env` thật.

## Database và an toàn dữ liệu

Compose hiện dùng volume `mysql_data_v2`, tương ứng tên Docker thường là `hnaj_mysql_data_v2`. Volume cũ `hnaj_mysql_data` được giữ nguyên vì QA phát hiện lỗi InnoDB corruption trong volume đó. Không dùng các lệnh sau nếu chưa có backup và phê duyệt:

```bash
docker compose down -v
docker volume rm ...
docker compose down --volumes
```

Các lệnh trên có thể xóa dữ liệu database. Volume mới là database sạch, nên migration mặc định phải được chạy sau lần khởi tạo.

## Secret và file môi trường

- `hnaj-docker/.env.example` chỉ chứa giá trị mẫu an toàn.
- `hnaj-docker/.env` chứa credential local và bị ignore.
- `hnaj-be/.env` chứa `APP_KEY` và cấu hình Laravel local; file bị ignore.
- Không đưa secret, password thật, private key hoặc dump dữ liệu nhạy cảm vào Git.

## Đường nâng cấp production

Dockerfile đã có target `production` cho backend và frontend. Development hiện bind mount source để hot reload. Production sau này cần dùng image build artifact bất biến, Composer `--no-dev`, frontend build thành static artifact và Nginx phục vụ `dist`; không bind mount source và không expose MySQL. Việc tạo compose production riêng cần được kiểm tra và duyệt như một phạm vi vận hành độc lập.
