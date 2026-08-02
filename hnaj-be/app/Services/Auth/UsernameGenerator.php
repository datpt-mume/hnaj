<?php

namespace App\Services\Auth;

use App\Repositories\UserRepository;
use Illuminate\Support\Str;

/**
 * Sinh username cho tài khoản đăng nhập bằng Google, vì Google không cung cấp username.
 * Lấy phần local của email, chuẩn hóa, thêm mã ngẫu nhiên để đảm bảo không trùng,
 * rồi nhường unique constraint trong database làm lớp bảo vệ cuối cùng.
 */
class UsernameGenerator
{
    private const MAX_LENGTH = 50;

    private const MIN_LENGTH = 3;

    private const RANDOM_SUFFIX_LENGTH = 6;

    public function __construct(private readonly UserRepository $users) {}

    /**
     * Tạo username duy nhất dạng `{local-part}_{random-bytes}`.
     * Kiểm tra thêm một lần trong database để giảm thiểu rủi ro trùng,
     * nhưng unique constraint vẫn là lớp phòng thủ cuối.
     */
    public function fromEmail(string $email): string
    {
        $base = $this->normalize(Str::before($email, '@'));
        $candidate = $this->withSuffix($base, Str::lower(Str::random(self::RANDOM_SUFFIX_LENGTH)));

        // Nếu xác suất trùng rất thấp vẫn xảy ra, thử lại với suffix khác.
        for ($attempt = 0; $attempt < 10; $attempt++) {
            if (! $this->users->usernameExists($candidate)) {
                return $candidate;
            }

            $candidate = $this->withSuffix($base, Str::lower(Str::random(self::RANDOM_SUFFIX_LENGTH)));
        }

        return $candidate;
    }

    /**
     * Tạo username mới không kiểm tra database, dùng trong retry khi duplicate ở tầng ghi.
     */
    public function generateNew(string $email): string
    {
        $base = $this->normalize(Str::before($email, '@'));

        return $this->withSuffix($base, Str::lower(Str::random(self::RANDOM_SUFFIX_LENGTH)));
    }

    private function normalize(string $value): string
    {
        $normalized = Str::lower(Str::ascii($value));
        $normalized = preg_replace('/[^a-z0-9_.]/', '', $normalized) ?? '';
        $normalized = trim($normalized, '._');
        $normalized = substr($normalized, 0, self::MAX_LENGTH);

        if (strlen($normalized) < self::MIN_LENGTH) {
            $normalized = 'user'.Str::lower(Str::random(6));
        }

        return $normalized;
    }

    private function withSuffix(string $base, string $suffix): string
    {
        // Reserve space for the underscore separator + suffix.
        $trimmed = substr($base, 0, self::MAX_LENGTH - strlen($suffix) - 1);

        return $trimmed.'_'.$suffix;
    }
}
