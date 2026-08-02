<?php

namespace App\Exceptions;

use App\Enums\AuthErrorCode;
use RuntimeException;

/**
 * Lỗi nghiệp vụ của luồng authentication.
 * Mang sẵn HTTP status và mã lỗi để handler dựng error envelope chung.
 */
class AuthFlowException extends RuntimeException
{
    public function __construct(
        public readonly AuthErrorCode $errorCode,
        string $message,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    public static function invalidCredentials(): self
    {
        return new self(
            AuthErrorCode::InvalidCredentials,
            'The provided credentials are incorrect.',
            401,
        );
    }

    public static function emailNotVerified(): self
    {
        return new self(
            AuthErrorCode::EmailNotVerified,
            'This email address has not been verified yet.',
            403,
        );
    }

    public static function accountNotActive(): self
    {
        return new self(
            AuthErrorCode::AccountNotActive,
            'This account is not active.',
            403,
        );
    }

    public static function invalidVerificationToken(): self
    {
        return new self(
            AuthErrorCode::InvalidToken,
            'This verification link is invalid or has expired.',
            422,
        );
    }

    public static function emailAlreadyVerified(): self
    {
        return new self(
            AuthErrorCode::AlreadyVerified,
            'This email address has already been verified.',
            409,
        );
    }

    public static function forbiddenRole(): self
    {
        return new self(
            AuthErrorCode::ForbiddenRole,
            'You do not have permission to access this resource.',
            403,
        );
    }

    public static function googleAuthFailed(string $message = 'Unable to complete Google sign-in.'): self
    {
        return new self(AuthErrorCode::GoogleAuthFailed, $message, 422);
    }
}
