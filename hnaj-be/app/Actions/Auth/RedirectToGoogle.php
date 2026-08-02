<?php

namespace App\Actions\Auth;

use App\Exceptions\AuthFlowException;
use App\Services\Auth\GoogleOAuthClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Bước 1 của luồng Google: sinh `state` một lần, lưu vào cache rồi dựng URL đồng ý.
 */
class RedirectToGoogle
{
    public const FLOW_COOKIE = 'hnaj_google_oauth_flow';
    public const FLOW_COOKIE_PATH = '/api/auth/google';

    /**
     * State chỉ cần sống đủ lâu cho người dùng chọn tài khoản Google.
     */
    private const STATE_TTL_SECONDS = 300;

    public function __construct(private readonly GoogleOAuthClient $google) {}

    public function handle(string $flowCookie): string
    {
        if (! $this->google->isConfigured()) {
            throw AuthFlowException::googleAuthFailed('Google sign-in is not configured.');
        }

        $state = Str::random(40);

        Cache::put($this->cacheKey($state), [
            'flow_hash' => self::hashFlowCookie($flowCookie),
        ], self::STATE_TTL_SECONDS);

        return $this->google->buildAuthorizationUrl($state);
    }

    public static function cacheKey(string $state): string
    {
        return 'auth:google:state:'.hash('sha256', $state);
    }

    public static function hashFlowCookie(string $flowCookie): string
    {
        return hash('sha256', $flowCookie);
    }

    public static function ttlMinutes(): int
    {
        return (int) ceil(self::STATE_TTL_SECONDS / 60);
    }
}
