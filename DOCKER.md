# Build all services
docker compose build

# Start BE (kèm MySQL) and FE
docker compose up -d

# Follow logs
docker compose logs -f be fe

# Stop services
docker compose down

# Stop and remove database volume
docker compose down -v

# Backend container contains both Laravel/PHP and MySQL.
# MySQL is available from Laravel at 127.0.0.1:3306.
