# HNAJ — Khám phá & Chia sẻ Địa điểm Hà Nội

Dự án xây dựng nền tảng khám phá và chia sẻ trải nghiệm địa điểm ăn uống, vui chơi, giải trí và du lịch tại Hà Nội. Repository được tổ chức theo mô hình chung gồm:

- `backend/` — Ứng dụng API sử dụng **Java 21** và **Spring Boot** (kèm sẵn **Maven Wrapper**).
- `frontend/` — Ứng dụng web giao diện tiếng Việt sử dụng **Next.js (App Router)**, **React** và **TypeScript strict**.
- `scripts/` — Script chạy kiểm tra kiểu dữ liệu (`npm run typecheck`) và kiểm thử (`npm test`) xuyên suốt cả hai ứng dụng từ thư mục gốc.

---

## 1. Yêu cầu công cụ cần cài đặt (Prerequisites)

Trước khi clone và chạy dự án, máy tính của bạn cần có các công cụ sau:

1. **Git** (`>= 2.40`)
2. **Java Development Kit (JDK) 21 LTS** (ví dụ: Eclipse Temurin 21)
3. **Node.js LTS** (`>= 22.x`) và **npm** (`>= 10.x`)

> **Lưu ý:** Không cần cài đặt Maven toàn cục vì thư mục `backend/` đã tích hợp sẵn **Maven Wrapper** (`mvnw` / `mvnw.cmd`).

### Hướng dẫn cài đặt công cụ trên Windows

#### Cách 1: Cài nhanh qua Winget (Khuyên dùng trên Windows 10/11)

Mở **PowerShell** và chạy:

```powershell
winget install --id Git.Git -e --source winget
winget install --id EclipseAdoptium.Temurin.21.JDK -e --source winget
winget install --id OpenJS.NodeJS.LTS -e --source winget
```

#### Cách 2: Tải bộ cài chính thức (Nếu máy không có Winget)

- **Git:** <https://git-scm.com/download/win>
- **Temurin JDK 21 (x64):** <https://adoptium.net/temurin/releases/?version=21> (Chọn bộ cài `.msi` và bật tùy chọn *Set JAVA_HOME variable* + *Add to PATH*).
- **Node.js 22 LTS (x64):** <https://nodejs.org/>

#### Cho phép chạy script `npm` trong PowerShell (nếu gặp lỗi ExecutionPolicy)

Nếu Windows PowerShell báo lỗi không cho chạy `npm.ps1`, hãy mở PowerShell và chạy một lần lệnh sau:

```powershell
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned -Force
```

#### Kiểm tra sau khi cài đặt

Mở cửa sổ terminal mới và kiểm tra:

```powershell
git --version
java -version
javac -version
node --version
npm --version
```

Đảm bảo biến môi trường `JAVA_HOME` trỏ tới thư mục gốc của JDK 21 (ví dụ: `C:\Program Files\Eclipse Adoptium\jdk-21...`).

---

## 2. Clone và cài đặt Dependencies

```powershell
git clone <url-repository> hnaj
cd hnaj

# Cài đặt dependencies cho Frontend
cd frontend
npm install
cd ..
```

Đối với `backend/`, Maven Wrapper sẽ tự động tải Maven và các thư viện cần thiết ở lần chạy hoặc kiểm thử đầu tiên.

---

## 3. Chạy ứng dụng ở môi trường cục bộ (Local Development)

Mở **2 cửa sổ terminal** tại thư mục gốc của dự án:

### Terminal 1 — Chạy Backend Spring Boot (Cổng `8080`)

```powershell
cd backend
.\mvnw.cmd spring-boot:run
```

Kiểm tra nhanh endpoint health của backend:

```powershell
curl.exe http://localhost:8080/api/health
# Phản hồi kỳ vọng: {"status":"UP"}
```

### Terminal 2 — Chạy Frontend Next.js (Cổng `3000`)

```powershell
cd frontend
npm run dev
```

Mở trình duyệt tại `http://localhost:3000`:

- Khi backend đang chạy: Trang hiển thị trạng thái **Đang hoạt động (UP)**.
- Khi backend tắt hoặc mất kết nối: Trang hiển thị trạng thái **Mất kết nối (DOWN)** kèm hướng dẫn kiểm tra mà không làm sập trang.

*(Tùy chọn)* Nếu backend chạy ở địa chỉ khác `http://localhost:8080`, có thể đặt biến môi trường `BACKEND_URL` cho tiến trình Next.js server.

---

## 4. Kiểm tra kiểu dữ liệu, Lint và Kiểm thử (Testing & Quality)

Từ **thư mục gốc** của repository, bạn có thể chạy toàn bộ vòng lặp kiểm tra cho cả Frontend lẫn Backend:

```powershell
# Kiểm tra TypeScript (Frontend) + Biên dịch kiểm tra kiểu Java (Backend)
npm run typecheck

# Chạy toàn bộ Unit/Component Tests (Frontend Vitest + Backend JUnit/MockMvc)
npm test
```

Hoặc chạy riêng từng phần:

```powershell
# Chỉ chạy kiểm tra ở Frontend
cd frontend
npm run typecheck
npm run lint
npm test
npm run build

# Chỉ chạy kiểm tra ở Backend
cd ..\backend
.\mvnw.cmd test
```
