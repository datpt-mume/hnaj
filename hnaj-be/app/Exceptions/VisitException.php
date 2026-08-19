<?php

namespace App\Exceptions;

use App\Enums\VisitErrorCode;
use RuntimeException;

/**
 * Lỗi nghiệp vụ của domain visit.
 * Mang sẵn HTTP status và mã lỗi để handler dựng error envelope chung.
 */
class VisitException extends RuntimeException
{
    public function __construct(
        public readonly VisitErrorCode $errorCode,
        string $message,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }
}