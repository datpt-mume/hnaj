<?php

namespace App\Enums;

/**
 * Trạng thái hiển thị comment.
 */
enum CommentStatus: string
{
    case Published = 'published'; // Hiển thị công khai
    case Hidden = 'hidden';       // Admin ẩn comment
    case Removed = 'removed';     // Bị gỡ vĩnh viễn
}
