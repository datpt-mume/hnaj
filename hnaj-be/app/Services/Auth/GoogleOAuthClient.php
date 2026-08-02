<?php

namespace App\Services\Auth;

use App\Exceptions\AuthFlowException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class GoogleOAuthClient
{
    private const AUTHORIZATION_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    public function isConfigured(): bool
    {
        return $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->redirectUri() !== '';
    }

    public function buildAuthorizationUrl(string $state): string
    {
        return self::AUTHORIZATION_URL.'?'.http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array{google_id: string, email: string, name: string, avatar_url: ?string, email_verified: bool}
     */
    public function fetchProfile(string $code): array
    {
        $accessToken = $this->exchangeCodeForAccessToken($code);

        try {
            $profile = Http::acceptJson()
                ->withToken($accessToken)
                ->timeout(10)
                ->get(self::USERINFO_URL)
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException) {
            throw AuthFlowException::googleAuthFailed();
        }

        if (! is_array($profile)
            || ! isset($profile['sub'], $profile['email'])
            || ! is_string($profile['sub'])
            || ! is_string($profile['email'])) {
            throw AuthFlowException::googleAuthFailed();
        }

        $name = isset($profile['name']) && is_string($profile['name'])
            ? $profile['name']
            : $profile['email'];

        return [
            'google_id' => $profile['sub'],
            'email' => $profile['email'],
            'name' => $name,
            'avatar_url' => isset($profile['picture']) && is_string($profile['picture'])
                ? $profile['picture']
                : null,
            'email_verified' => (bool) ($profile['email_verified'] ?? false),
        ];
    }

    private function exchangeCodeForAccessToken(string $code): string
    {
        try {
            $payload = Http::asForm()
                ->acceptJson()
                ->timeout(10)
                ->post(self::TOKEN_URL, [
                    'client_id' => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $this->redirectUri(),
                ])
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException) {
            throw AuthFlowException::googleAuthFailed();
        }

        if (! is_array($payload)
            || ! isset($payload['access_token'])
            || ! is_string($payload['access_token'])) {
            throw AuthFlowException::googleAuthFailed();
        }

        return $payload['access_token'];
    }

    private function clientId(): string
    {
        return trim((string) config('services.google.client_id'));
    }

    private function clientSecret(): string
    {
        return trim((string) config('services.google.client_secret'));
    }

    private function redirectUri(): string
    {
        return trim((string) config('services.google.redirect_uri'));
    }
}
