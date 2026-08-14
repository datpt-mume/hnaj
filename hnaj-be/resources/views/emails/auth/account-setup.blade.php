<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Kích hoạt tài khoản HNAJ</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:Helvetica,Arial,sans-serif;color:#18181b;">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border:1px solid #e4e4e7;border-radius:12px;padding:32px;">
        <h1 style="margin:0 0 16px;font-size:20px;line-height:1.3;">Kích hoạt tài khoản quản lý địa điểm</h1>

        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Chào {{ $user->name }}, bạn đã được cấp quyền quản lý địa điểm trên Hôm nay ăn gì với tên tài khoản
            <strong>{{ $user->username }}</strong>.
        </p>

        <p style="margin:0 0 24px;font-size:15px;line-height:1.6;">
            Bấm vào nút bên dưới để đặt mật khẩu và kích hoạt tài khoản.
            Liên kết chỉ dùng được một lần và hết hạn sau {{ $expiresInHours }} giờ.
        </p>

        <p style="margin:0 0 24px;">
            <a href="{{ $setupUrl }}"
               style="display:inline-block;background:#166534;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-size:15px;font-weight:600;">
                Kích hoạt tài khoản
            </a>
        </p>

        <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#52525b;">
            Nếu nút không hoạt động, sao chép liên kết sau vào trình duyệt:
        </p>

        <p style="margin:0 0 24px;font-size:13px;line-height:1.6;word-break:break-all;">
            <a href="{{ $setupUrl }}" style="color:#166534;">{{ $setupUrl }}</a>
        </p>

        <p style="margin:0;font-size:13px;line-height:1.6;color:#52525b;">
            Nếu bạn không yêu cầu tài khoản này, hãy bỏ qua email.
        </p>
    </div>
</body>
</html>
