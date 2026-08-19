<?php

namespace App\Enums;

/**
 * Mã lỗi ổn định cho domain visit (docs/api-visits.md).
 */
enum VisitErrorCode: string
{
    case PlaceNotAvailable = 'VISIT_PLACE_NOT_AVAILABLE';
    case AnonymousKeyRequired = 'VISIT_ANONYMOUS_KEY_REQUIRED';
}