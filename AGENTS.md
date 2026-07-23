# AGENTS.md — Hướng dẫn làm việc cho AI agent

## 1. Mục đích và phạm vi

File này là nguồn hướng dẫn chung cho mọi AI agent làm việc trong toàn bộ repository HNAJ. Các quy tắc áp dụng cho mọi file và thư mục, trừ khi yêu cầu trực tiếp của người dùng quy định khác.

Mục tiêu của agent:

- Thực hiện thay đổi đúng yêu cầu, an toàn, có thể kiểm chứng và dễ bảo trì.
- Giữ backend, frontend, REST API, database và Docker đồng bộ.
- Ưu tiên thay đổi nhỏ, rõ ràng, đúng phạm vi thay vì refactor diện rộng.
- Không suy đoán thông tin có thể xác minh từ repository hoặc cần người dùng quyết định.

Cấu trúc chính:

```text
hnaj/
├── AGENTS.md       # Hướng dẫn chung cho agent
├── .agents/
│   ├── rules/      # Quy tắc bổ sung theo phạm vi hoặc chuyên môn
│   └── skills/     # Quy trình và checklist cho từng loại nhiệm vụ
├── hnaj-be/        # Backend PHP Laravel và REST API
├── hnaj-fe/        # Frontend React, React Router và REST client
└── hnaj-docker/    # Docker cho backend, frontend và MySQL
```

Đây là một repository duy nhất. Thay đổi trong một thư mục có thể ảnh hưởng các thư mục còn lại; không được coi chúng là các dự án hoàn toàn độc lập.

### 1.1. Bắt buộc sử dụng rules và skills trong `.agents`

Khi bắt đầu mỗi task, agent phải kiểm tra cả `.agents/rules/` và `.agents/skills/` trước khi lập kế hoạch:

1. Đọc và áp dụng mọi rule có phạm vi phù hợp với task đang thực hiện.
2. Tìm skill liên quan đến loại task, công nghệ hoặc khu vực bị ảnh hưởng; đọc đầy đủ hướng dẫn của skill trước khi đề xuất kế hoạch.
3. Tuân thủ workflow, checklist, tiêu chí kiểm chứng và định dạng đầu ra do skill phù hợp quy định.
4. Không áp dụng skill không liên quan chỉ vì skill đó tồn tại.
5. Ghi rõ trong kế hoạch những rule và skill chuyên biệt đã áp dụng khi chúng ảnh hưởng cách triển khai hoặc kiểm chứng.

Rule hoặc skill trong `.agents/` không được dùng để bỏ qua yêu cầu phê duyệt, quy tắc bảo mật hay thao tác bị cấm trong file này. Nếu rule/skill bị thiếu, không rõ, mâu thuẫn nhau hoặc không tương thích với repository hiện tại, agent phải nêu vấn đề và hỏi người dùng; không tự đoán hoặc âm thầm bỏ qua.

## 2. Thứ tự ưu tiên và nguồn sự thật

Khi có xung đột, áp dụng thứ tự ưu tiên sau:

1. Yêu cầu trực tiếp, mới nhất và rõ ràng của người dùng.
2. Quy tắc trong file này.
3. Rule phù hợp trong `.agents/rules/`.
4. Skill phù hợp trong `.agents/skills/`.
5. Tài liệu, manifest, cấu hình, script và convention hiện có trong repository.
6. Convention chính thức của framework hoặc thư viện đang được repository sử dụng.
7. Best practice chung của ngành.

Không được tự ý thay đổi yêu cầu để khớp với sở thích của agent. Nếu hai chỉ dẫn cùng mức ưu tiên mâu thuẫn hoặc yêu cầu chưa đủ rõ, phải nêu mâu thuẫn và hỏi người dùng trước khi sửa code.

Các nguồn sự thật kỹ thuật gồm, nhưng không giới hạn:

- Backend: manifest PHP, cấu hình Laravel, routes, Form Request, API Resource, migration và test hiện có.
- Frontend: manifest package, lockfile, script, cấu hình build/lint, routes, service gọi API và test hiện có.
- Docker: Compose file, Dockerfile, file môi trường mẫu, healthcheck, network và volume hiện có.
- API: tài liệu Markdown của API, routes Laravel, Form Request, API Resource và mã frontend đang tiêu thụ API.

Nếu các nguồn sự thật không đồng bộ, không tự chọn một phía rồi âm thầm sửa. Phải báo rõ sai lệch, đề xuất hướng đồng bộ và chờ duyệt.

## 3. Không tự đoán công nghệ hoặc lệnh

Không chỉ định hoặc tự suy đoán:

- Phiên bản PHP, Laravel, Node.js, React, MySQL hoặc Docker.
- Package manager, test runner, formatter, linter hoặc bundler.
- Lệnh cài đặt, build, test, lint, format, migrate hoặc khởi chạy.
- Cơ chế authentication, authorization, cache, queue hoặc deployment.
- Port, domain, tên database, credential hoặc biến môi trường.

Trước tiên phải đọc manifest, lockfile, config, script và tài liệu hiện có. Nếu chưa có đủ dữ liệu để xác định, phải hỏi người dùng. Không được tạo convention mới chỉ để tiếp tục task.

Giữ nguyên package manager và lockfile đang dùng. Không trộn nhiều package manager hoặc tự tái tạo lockfile bằng công cụ khác.

## 4. Ngôn ngữ và cách giao tiếp

- Trao đổi với người dùng bằng tiếng Việt.
- Tài liệu dự án viết bằng tiếng Việt, trừ khi người dùng yêu cầu khác.
- Source code, identifier, tên class/function/variable, type và comment trong code viết bằng tiếng Anh.
- Nội dung hướng tới người dùng cuối tuân theo ngôn ngữ sản phẩm hoặc convention hiện có.
- Comment giải thích lý do, ràng buộc hoặc quyết định không hiển nhiên; không diễn giải lại code.
- Trình bày ngắn gọn nhưng phải nêu đủ giả định, rủi ro, kết quả kiểm chứng và việc chưa hoàn thành.

## 5. Quy trình làm việc bắt buộc

### 5.1. Khảo sát

Agent được phép thực hiện thao tác chỉ đọc để hiểu task mà không cần xin duyệt riêng. Trước khi đề xuất thay đổi:

1. Đọc file này và các file liên quan trực tiếp.
2. Xác định phạm vi ảnh hưởng ở backend, frontend, API, database và Docker.
3. Tìm implementation, convention, test và tài liệu tương tự đã tồn tại.
4. Đọc manifest, script và config liên quan; không đoán lệnh.
5. Kiểm tra trạng thái Git để tránh ghi đè thay đổi chưa liên quan của người dùng hoặc agent khác.
6. Ghi nhận điểm chưa rõ, giả định, rủi ro và khả năng tương thích ngược.

Không đọc secret thật hoặc dữ liệu nhạy cảm nếu task không bắt buộc. Không đưa secret vào log, phản hồi, tài liệu, test fixture hoặc source code.

### 5.2. Lập kế hoạch và chờ duyệt

Trước mọi thay đổi file hoặc thao tác làm thay đổi trạng thái hệ thống, phải trình bày kế hoạch và chờ người dùng duyệt. Kế hoạch tối thiểu gồm:

- Mục tiêu và phạm vi task.
- Hành vi hiện tại và hành vi mong muốn.
- Các khu vực/file dự kiến thay đổi.
- Ảnh hưởng đến API, database, frontend, Docker và tương thích ngược.
- Test, lint, build hoặc cách kiểm chứng dự kiến.
- Giả định, rủi ro và quyết định cần người dùng xác nhận.

Không được coi việc người dùng mô tả task là phê duyệt mặc định cho một kế hoạch chưa trình bày. Sau khi kế hoạch được duyệt, chỉ triển khai trong phạm vi đó. Nếu phát hiện yêu cầu mới hoặc phạm vi tăng đáng kể, phải dừng, cập nhật kế hoạch và xin duyệt lại.

### 5.3. Triển khai

- Thay đổi tối thiểu đủ để giải quyết yêu cầu.
- Bám cấu trúc và style hiện có.
- Không refactor, rename hoặc format hàng loạt ngoài phạm vi.
- Không sửa test để che giấu bug hoặc làm test pass sai bản chất.
- Không bỏ validation, authorization, type safety, error handling hoặc security control để đi đường tắt.
- Không dùng workaround im lặng; nếu giải pháp tạm thời là cần thiết, phải giải thích và xin duyệt.
- Không ghi đè hoặc hoàn tác thay đổi không thuộc task.

### 5.4. Kiểm chứng

Sau khi triển khai:

1. Rà soát diff để phát hiện file ngoài phạm vi, secret, debug code và thay đổi vô tình.
2. Chạy test/lint/format/build liên quan bằng script thực tế của repository.
3. Với thay đổi xuyên lớp, kiểm tra contract từ route/backend đến service/frontend và môi trường Docker.
4. Không tuyên bố thành công nếu chưa kiểm chứng.
5. Nếu không thể chạy một bước, báo rõ lệnh/bước chưa chạy, lý do và rủi ro còn lại.

### 5.5. Bàn giao

Báo cáo cuối cùng phải gồm:

- Tóm tắt hành vi đã thay đổi.
- Danh sách khu vực hoặc file chính đã sửa.
- Test/lint/build đã chạy và kết quả.
- Migration, biến môi trường hoặc bước vận hành cần chú ý.
- Rủi ro, giới hạn và phần chưa kiểm chứng.

Không phóng đại kết quả và không nói đã chạy kiểm tra khi thực tế chưa chạy.

## 6. Các thao tác luôn cần phê duyệt rõ ràng

Dù kế hoạch tổng quát đã được duyệt, phải nêu rõ và nhận phê duyệt trước khi:

- Thêm, xóa, thay thế hoặc nâng cấp dependency.
- Thay package manager, framework, công cụ build/test/lint/format hoặc lockfile strategy.
- Tạo breaking change cho API, URL, payload, response, error format hoặc authentication flow.
- Thay đổi database schema hoặc migration có nguy cơ mất/biến đổi dữ liệu.
- Chạy lệnh destructive như drop, truncate, reset, fresh, prune, xóa volume hoặc xóa dữ liệu.
- Đọc, thay đổi hoặc sử dụng credential/secret thật.
- Đổi cấu trúc thư mục lớn, rename module/public API hoặc refactor diện rộng.
- Thay đổi Docker network, volume, exposed port hoặc cấu hình gần production có ảnh hưởng vận hành.
- Commit, amend, rebase, reset, force push, push hoặc sửa lịch sử Git.

Phê duyệt phải dựa trên mô tả cụ thể về thao tác và ảnh hưởng; không dùng câu hỏi chung chung để hợp thức hóa nhiều hành động rủi ro.

## 7. Kiến trúc backend Laravel

Mọi feature backend phải tuân theo luồng phân lớp:

```text
Route
  → Controller
    → Service hoặc Action
      → Repository
        → Model / Database
```

Dữ liệu trả về API đi qua API Resource khi phù hợp:

```text
Form Request → Controller → Service/Action → Repository → API Resource
```

### 7.1. Trách nhiệm từng lớp

- **Route:** khai báo endpoint, middleware và binding; không chứa business logic.
- **Form Request:** validation và authorization cấp request khi phù hợp; không đặt business workflow.
- **Controller:** nhận request đã validate, gọi Service/Action, trả response; phải mỏng.
- **Service:** điều phối business workflow có nhiều bước hoặc nhiều dependency.
- **Action:** biểu diễn một use case hoặc thao tác nghiệp vụ tập trung, có tên rõ nghĩa.
- **Repository:** cô lập truy vấn/persistence; không chứa HTTP concern hoặc presentation logic.
- **Model:** quan hệ, cast, scope và hành vi domain phù hợp; tránh biến model thành nơi chứa mọi logic.
- **API Resource:** định hình response public; không để lộ field nội bộ ngoài ý muốn.

Chọn Service hoặc Action theo convention hiện có. Không tạo hai lớp chỉ để chuyển tiếp dữ liệu mà không mang lại ranh giới hoặc giá trị rõ ràng. Tuy nhiên, không được bỏ Repository và đặt truy vấn trực tiếp trong Controller/Service nếu feature thuộc luồng phân lớp trên.

### 7.2. Quy tắc backend

- Không truy vấn database trực tiếp trong Controller.
- Không đặt business logic trong Route, Controller, Form Request hoặc API Resource.
- Dùng dependency injection theo convention Laravel và code hiện có.
- Validation phải nằm ở biên hệ thống; không tin dữ liệu từ frontend.
- Authentication và authorization phải được thực thi ở backend; UI guard không phải security boundary.
- Trả HTTP status đúng ngữ nghĩa và không nuốt exception.
- Không trả stack trace, query, secret hoặc chi tiết nội bộ cho client.
- Dùng transaction khi một nghiệp vụ gồm nhiều thay đổi dữ liệu phải thành công hoặc thất bại cùng nhau.
- Chủ động kiểm tra N+1, eager loading, pagination và query dư thừa.
- Không tối ưu sớm nếu chưa có bằng chứng, nhưng không chấp nhận pattern truy vấn rõ ràng gây hại.
- Giữ backward compatibility trừ khi breaking change đã được duyệt.

### 7.3. Migration và dữ liệu

- Mọi migration phải có phương án rollback hợp lý trong `down()`.
- Không chạy migration destructive hoặc command làm mất dữ liệu nếu chưa được duyệt rõ ràng.
- Thay đổi schema phải xem xét dữ liệu hiện có, default, nullability, index, foreign key và rollback.
- Thay đổi dữ liệu lớn cần kế hoạch rollout/rollback và đánh giá lock/downtime.
- Seeder/factory không được chứa secret hoặc dữ liệu production thật.
- Không sửa migration đã được dùng ở môi trường chia sẻ nếu convention dự án yêu cầu migration bổ sung; phải kiểm tra bối cảnh trước.

## 8. Kiến trúc frontend React

Mọi feature frontend phải tách trách nhiệm theo các nhóm sau, phù hợp với cấu trúc hiện có:

```text
pages/       # Màn hình gắn với route và orchestration cấp trang
components/  # UI component có thể tái sử dụng
hooks/       # State/effect logic và hành vi tái sử dụng
services/    # REST API client và tích hợp external service
```

Có thể có thêm các nhóm như `layouts`, `contexts`, `utils`, `types` hoặc `features` nếu repository đã dùng hoặc kế hoạch bổ sung đã được duyệt.

### 8.1. Quy tắc frontend

- React Router là lớp định tuyến; route config phải nhất quán với convention hiện có.
- Mọi HTTP request phải đi qua service layer; không gọi REST API trực tiếp rải rác trong page/component.
- Page điều phối dữ liệu và bố cục cấp màn hình; component tập trung vào UI và interaction.
- Tách state/effect logic phức tạp hoặc tái sử dụng thành hook.
- Không đặt business/data-access logic phức tạp trong component trình bày.
- Không tạo abstraction hoặc component dùng chung khi mới chỉ có một nhu cầu giả định.
- Không hard-code API base URL, secret hoặc cấu hình phụ thuộc môi trường.
- Tuân theo strategy quản lý state, styling, typing và error handling đang tồn tại; nếu chưa có thì hỏi.
- Không thay router, state library, CSS approach hoặc build tool nếu chưa được duyệt.

### 8.2. Trạng thái UI và trải nghiệm

Mỗi luồng bất đồng bộ phải cân nhắc đầy đủ:

- Loading state, tránh submit hoặc request trùng khi cần.
- Error state có thông báo hữu ích và cách retry/khôi phục phù hợp.
- Empty state cho danh sách hoặc dữ liệu không tồn tại.
- Success/feedback state cho thao tác thay đổi dữ liệu.
- Permission/unauthenticated state nếu feature có phân quyền.

Giao diện phải:

- Responsive ở các kích thước màn hình phù hợp với sản phẩm.
- Dùng semantic HTML trước khi dùng ARIA.
- Hỗ trợ thao tác bàn phím và focus rõ ràng cho control tương tác.
- Có label/name truy cập được cho input, button và icon action.
- Duy trì contrast và không chỉ dùng màu sắc để truyền đạt trạng thái.
- Không phá hành vi browser cơ bản hoặc accessibility để đổi lấy hiệu ứng hình ảnh.

## 9. REST API và contract backend–frontend

### 9.1. Nguyên tắc API

- Resource naming, URL và pluralization phải nhất quán.
- Dùng HTTP method và status code đúng ngữ nghĩa.
- Mọi API response phải dùng chung envelope success hoặc error; không trả payload JSON tự do theo từng endpoint.
- Success response có dạng `{ "success": true, "message": "...", "data": ..., "meta": {} }`; `meta` là tùy chọn.
- Error response có dạng `{ "success": false, "message": "...", "errors": {}, "code": "..." }`; `errors` và `code` là tùy chọn.
- `success` phải phản ánh kết quả nghiệp vụ độc lập với HTTP status; dùng HTTP method và status code đúng ngữ nghĩa.
- Validation, 404 và exception phát sinh từ API phải được chuyển về error envelope chung; không lộ stack trace, query, secret hoặc chi tiết nội bộ.
- Request/response/error format phải nhất quán với contract và tài liệu API.
- Danh sách có thể lớn phải hỗ trợ pagination; filter/sort/search phải có contract rõ ràng.
- Validation error phải có cấu trúc ổn định để frontend xử lý.
- Không lộ database schema, exception nội bộ hoặc field nhạy cảm.
- Cân nhắc idempotency, concurrency và retry đối với thao tác phù hợp.

### 9.2. Nguồn contract và quy trình thay đổi

API contract được tài liệu hóa bằng Markdown, kèm ví dụ request/response. Tài liệu, Laravel routes, Form Request, API Resource và frontend service phải đồng bộ.

Trước khi đề xuất thay đổi API, agent phải:

1. Kiểm tra route và middleware liên quan.
2. Kiểm tra Form Request/validation và authorization.
3. Kiểm tra API Resource hoặc response mapping.
4. Kiểm tra tài liệu Markdown và ví dụ request/response.
5. Tìm mọi nơi frontend đang gọi hoặc phụ thuộc endpoint/payload đó.
6. Phân loại thay đổi là backward-compatible hay breaking.
7. Nêu kế hoạch cập nhật đồng bộ backend, frontend, tài liệu và test.

Mọi breaking change phải được người dùng duyệt rõ ràng trước khi triển khai. Không được đổi contract ở backend mà bỏ lại frontend hoặc tài liệu không tương thích.

Nếu tài liệu và code khác nhau, phải báo sai lệch và hỏi nguồn nào phản ánh ý định đúng; không âm thầm coi một bên là đúng tuyệt đối.

## 10. Database MySQL và bảo mật dữ liệu

- MySQL chạy trong một Docker service riêng.
- Kết nối database phải qua biến môi trường/config, không hard-code credential.
- Không commit `.env`, private key, token, password, dump nhạy cảm hoặc credential thật.
- Chỉ commit file môi trường mẫu với giá trị giả/an toàn và mô tả biến cần thiết.
- Không truy cập, sao chép hoặc biến đổi dữ liệu production nếu chưa có quy trình và phê duyệt cụ thể.
- Không chạy lệnh phá dữ liệu hoặc xóa volume.
- Validation, authentication và authorization phải theo cơ chế Laravel thực tế của dự án.
- Khi nhận thấy rủi ro vượt rule hiện có, agent phải đề xuất biện pháp như rate limiting, audit log, encryption, transaction, index hoặc policy; không tự thêm nếu làm thay đổi kiến trúc/phạm vi.

## 11. Docker và môi trường chạy

Mục tiêu là clone repository và khởi chạy toàn bộ hệ thống bằng quy trình ngắn, có thể lặp lại và gần production. Backend, frontend và MySQL đều chạy trong container.

### 11.1. Tiêu chuẩn container

- Ưu tiên image chính thức, nhỏ và phiên bản được xác định từ cấu hình dự án; không tự chọn version.
- Dùng multi-stage build khi giúp giảm kích thước hoặc tách build/runtime hợp lý.
- Runtime process chạy bằng non-root user khi image và workload cho phép.
- Chỉ copy artifact/dependency cần thiết vào runtime image.
- Có `.dockerignore` phù hợp; không đưa Git metadata, secret, cache hoặc dependency host không cần thiết vào build context.
- Có healthcheck thực sự phản ánh khả năng phục vụ, không chỉ kiểm tra process tồn tại.
- Xử lý signal và shutdown đúng để container dừng an toàn.
- Không bake secret hoặc environment-specific config vào image.

### 11.2. Compose, network và dữ liệu

- Compose phải mô tả rõ backend, frontend và MySQL cùng dependency cần thiết.
- Dùng service name cho giao tiếp nội bộ; không hard-code host phụ thuộc máy cá nhân.
- Chỉ expose port cần thiết.
- MySQL dùng named volume để giữ dữ liệu.
- Không xóa/recreate volume hoặc reset database khi chưa được duyệt.
- `depends_on` không thay thế readiness; dùng healthcheck/wait strategy phù hợp với công cụ hiện có.
- Cấu hình qua biến môi trường và file mẫu không chứa secret.
- Tránh bind mount hoặc dev-only behavior trong cấu hình production-like nếu làm runtime lệch production; nếu cần dev override, phải tách và tài liệu hóa.
- Mọi thay đổi port, network, volume, hostname hoặc env contract phải kiểm tra đồng bộ BE, FE và tài liệu bootstrap.

## 12. Kiểm thử và tiêu chí hoàn thành

### 12.1. Yêu cầu kiểm thử

- Bug fix phải có regression test chứng minh lỗi cũ và bảo vệ hành vi mới, trừ khi về kỹ thuật không khả thi; khi đó phải giải thích.
- Feature quan trọng phải có test phù hợp với rủi ro và lớp thay đổi.
- Backend ưu tiên test ở boundary/use case phù hợp và bổ sung unit test cho logic cô lập khi có giá trị.
- Frontend ưu tiên test hành vi người dùng và contract service thay vì test implementation detail.
- Thay đổi UI nhỏ có thể không cần test tự động mới, nhưng phải chạy build và lint hiện có, đồng thời kiểm tra thủ công khi có thể.
- Thay đổi API phải kiểm tra success, validation error, authorization và edge case phù hợp.
- Thay đổi migration/repository phải kiểm tra constraint, rollback và hành vi dữ liệu liên quan khi có thể.

Không đặt coverage target mới nếu repository/người dùng chưa quy định. Không giảm coverage hoặc xóa test chỉ để hoàn thành task.

### 12.2. Definition of Done

Một task chỉ được xem là hoàn thành khi:

- Hành vi đáp ứng yêu cầu và phạm vi đã duyệt.
- Kiến trúc BE/FE tuân thủ quy tắc phân lớp.
- API, frontend consumer và tài liệu contract được đồng bộ khi liên quan.
- Validation, authorization, error handling và edge case phù hợp đã được xem xét.
- Test cần thiết đã được thêm/cập nhật.
- Test, lint, format và build liên quan đã chạy theo script thực tế và đạt, hoặc giới hạn đã được báo rõ.
- Docker config/bootstrap được kiểm tra khi thay đổi ảnh hưởng runtime.
- Không còn debug code, secret, file ngoài phạm vi hoặc thay đổi vô tình.
- Diff cuối cùng đã được rà soát.
- Báo cáo bàn giao trung thực và đầy đủ.

## 13. Git và quản lý thay đổi

- Một task nên tạo diff tập trung, dễ review.
- Không trộn cleanup/refactor không liên quan vào feature hoặc bug fix.
- Không ghi đè thay đổi chưa commit của người dùng hoặc agent khác.
- Không tự commit, push, amend, rebase, reset hoặc sửa lịch sử Git.
- Khi người dùng yêu cầu tạo commit và đã duyệt nội dung, dùng Conventional Commits theo convention repository.
- Commit message phải mô tả đúng phạm vi; không ghi `fix` hoặc `feat` nếu nội dung không tương ứng.
- Không dùng destructive Git command để giải quyết conflict hoặc làm sạch workspace.

Ví dụ loại commit có thể dùng khi phù hợp: `feat`, `fix`, `refactor`, `test`, `docs`, `build`, `ci`, `chore`. Đây không phải danh sách bắt buộc nếu repository có convention cụ thể hơn.

## 14. Những điều không được làm

- Không code trước khi kế hoạch được duyệt.
- Không tự cài dependency hoặc tự chọn version/tool.
- Không tạo breaking API change khi chưa được duyệt.
- Không chạy migration/lệnh destructive hoặc xóa Docker volume.
- Không commit secret, `.env` thật hoặc dữ liệu nhạy cảm.
- Không đặt business logic/truy vấn trong Controller hoặc HTTP call trong component.
- Không coi frontend validation/route guard là cơ chế bảo mật.
- Không bỏ qua test/lint/build rồi tuyên bố hoàn thành.
- Không sửa/xóa test chỉ để che lỗi.
- Không refactor, rename hoặc format hàng loạt ngoài phạm vi.
- Không tự commit/push hoặc sửa lịch sử Git.
- Không phát minh file, command, kết quả hoặc trạng thái hệ thống chưa được xác minh.

## 15. Checklist nhanh cho agent

### Trước khi đề xuất kế hoạch

- [ ] Đã đọc `AGENTS.md` và yêu cầu mới nhất.
- [ ] Đã kiểm tra `.agents/rules/`, đọc và áp dụng các rule phù hợp.
- [ ] Đã kiểm tra `.agents/skills/`, đọc đầy đủ và áp dụng các skill phù hợp.
- [ ] Đã khảo sát file, manifest, config, script và test liên quan.
- [ ] Đã kiểm tra ảnh hưởng BE, FE, API, database và Docker.
- [ ] Đã kiểm tra code/convention tương tự hiện có.
- [ ] Đã nêu điểm chưa rõ, giả định, rủi ro và breaking change.
- [ ] Kế hoạch có file/khu vực dự kiến sửa và cách kiểm chứng.

### Trước khi sửa

- [ ] Người dùng đã duyệt kế hoạch cụ thể.
- [ ] Không có dependency, destructive action hoặc thay đổi contract chưa được duyệt.
- [ ] Phạm vi triển khai không vượt kế hoạch.

### Trước khi bàn giao

- [ ] Diff chỉ chứa thay đổi thuộc task.
- [ ] Không có secret, debug code hoặc file sinh ra ngoài ý muốn.
- [ ] Backend, frontend, API docs và Docker đã đồng bộ khi liên quan.
- [ ] Regression test/feature test đã được bổ sung khi cần.
- [ ] Test, lint, format và build liên quan đã chạy theo script thật.
- [ ] Mọi bước chưa chạy và rủi ro còn lại đã được báo rõ.
- [ ] Agent không tự commit hoặc push.
