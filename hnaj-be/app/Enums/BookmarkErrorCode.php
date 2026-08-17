<?php

namespace App\Enums;

/**
 * Mã lỗi ổn định cho domain bookmark (docs/api-bookmarks.md).
 */
enum BookmarkErrorCode: string
{
    case AlreadyExists = 'BOOKMARK_ALREADY_EXISTS';
    case PlaceNotAvailable = 'BOOKMARK_PLACE_NOT_AVAILABLE';
    case NotFound = 'BOOKMARK_NOT_FOUND';
}
