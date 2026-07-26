<?php

namespace App\Enums;

/**
 * Trạng thái hiển thị review.
 */
enum ReviewStatus: string
{
    case Published = 'published'; // Hiển thị công khai trên trang place
    case Hidden = 'hidden';        // Admin ẩn, không hiển thị nhưng vẫn còn trong database
    case Removed = 'removed';      // Bị gỡ vĩnh viễn, không hiển thị nữa
}
