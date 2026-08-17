<?php

namespace App\Exceptions;

use App\Enums\BookmarkErrorCode;
use RuntimeException;

/**
 * Lỗi nghiệp vụ của domain bookmark.
 * Mang sẵn HTTP status và mã lỗi để handler dựng error envelope chung.
 */
class BookmarkException extends RuntimeException
{
    public function __construct(
        public readonly BookmarkErrorCode $errorCode,
        string $message,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }
}
