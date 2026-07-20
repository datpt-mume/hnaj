# HNaj — Gợi ý địa điểm theo ngữ cảnh

Stack: **Laravel 13 (PHP 8.3) + React 19 (Vite 8/TypeScript) + MariaDB (MySQL-compatible)**

## Yêu cầu hệ thống

Trước khi clone dự án, máy cần có:

- **Git**
- **Docker Engine** hoặc **Docker Desktop** (phiên bản hỗ trợ Docker Compose plugin, `docker compose` không dấu gạch ngang)
- Quyền chạy Docker (thường là thành viên nhóm `docker` hoặc `sudo`)
- Các port sau phải trống: `8000`, `5173`, `3307`

## Clone và chạy lần đầu

```bash
git clone <repo-url> hnaj
cd hnaj
```

Tùy chọn: tạo file `.env` ở thư mục gốc để ghi đè port hoặc thông tin DB. Tất cả biến đều có giá trị mặc định:

```bash
BE_PORT=8000
FE_PORT=5173
DB_PORT=3307
DB_DATABASE=hnaj
DB_USERNAME=hnaj
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root
VITE_API_BASE_URL=http://localhost:8000/api
```

Sau đó chạy:

```bash
docker compose up --build
```

Lần đầu chạy, BE entrypoint sẽ tự động:

1. Khởi động MariaDB.
2. Tạo database và user.
3. Chạy `composer install`.
4. Generate `APP_KEY`.
5. Chạy `php artisan migrate`.
6. Bắt đầu `php artisan serve` trên port 8000.

FE container sẽ tự chạy `npm ci` (nếu cần) và `npm run dev` trên port 5173.

## Graphify trong Docker

Graphify được đóng gói thành service riêng trong profile `tools`. Package PyPI
chính thức là `graphifyy==0.9.20`, còn lệnh sử dụng là `graphify`.

```bash
docker compose --profile tools build graphify
docker compose --profile tools run --rm graphify --version
docker compose --profile tools run --rm graphify extract /workspace --code-only --force
```

Lệnh `--code-only` phân tích AST cục bộ, không cần API key và ghi kết quả vào
`graphify-out/`. Phân tích tài liệu, PDF, ảnh hoặc media có thể cần cấu hình
LLM backend/API key; xem [DOCKER.md](DOCKER.md) để biết các lệnh query.

## URL truy cập

| Thành phần | URL |
|---|---|
| Frontend (ứng dụng) | http://localhost:5173 |
| Backend API | http://localhost:8000/api/v1 |
| Health check | http://localhost:8000/up |
| Database (từ host) | `127.0.0.1:3307`, database `hnaj`, user `hnaj` / `secret` hoặc `root` / `root` |

## Lệnh thường dùng (chạy trong container)

Tất cả lệnh runtime đều phải chạy bên trong Docker container, không chạy trực tiếp trên host.

### Backend (`be` container)

```bash
# Artisan
docker compose exec -T be php artisan route:list --path=api
docker compose exec -T be php artisan migrate
docker compose exec -T be php artisan migrate:status
docker compose exec -T be php artisan db:show

# Composer
docker compose exec -T be composer install
docker compose exec -T be composer require <package>

# Test & lint
docker compose exec -T be php artisan test
docker compose exec -T be ./vendor/bin/pint --test

# Kiểm tra syntax PHP
docker compose exec -T be php -l app/Models/Place.php
```

### Frontend (`fe` container)

```bash
docker compose exec -T fe npm ci
docker compose exec -T fe npm run lint
docker compose exec -T fe npm run build
```

### Database shell

```bash
docker compose exec -T be mysql -uroot -p${DB_ROOT_PASSWORD:-root} hnaj
```

### Logs

```bash
docker compose logs -f be
docker compose logs -f fe
```

### Dừng / khởi động lại

```bash
docker compose down          # dừng container, giữ volume (DB không mất)
docker compose down -v       # dừng và XÓA volume — mất toàn bộ dữ liệu DB!
docker compose up --build    # rebuild image và khởi động lại
```

## Xử lý sự cố thường gặp

| Vấn đề | Cách xử lý |
|---|---|
| Port đã bị chiếm | Đổi port trong `.env` (vd `BE_PORT=8001`), hoặc tắt tiến trình đang dùng port đó |
| Container không khởi động | Xem logs: `docker compose logs be` hoặc `docker compose logs fe` |
| Migration lỗi | Vào BE container reset: `docker compose exec -T be php artisan migrate:fresh` |
| `composer install` thất bại | Vào BE container chạy lại: `docker compose exec -T be composer install` |
| Cần rebuild từ đầu | `docker compose down -v && docker compose up --build` (cảnh báo: mất DB data) |
| Muốn giữ DB khi rebuild | Chỉ chạy `docker compose up --build` (không dùng `-v`) |

## Lưu ý về database

- **Hiện tại** MariaDB chạy trong cùng container `be`, được mount vào Docker volume `mysql_data`. Dữ liệu tồn tại qua các lần restart.
- **Tương lai** MySQL 8 sẽ được tách thành service Docker Compose riêng (đã có trong thiết kế mục tiêu, chưa triển khai).
- Không dùng SQLite trên host để kiểm tra MySQL behavior — mọi kiểm tra DB phải chạy trong container.
