<?php

namespace App\Enums;

/**
 * Hành động moderation mà Admin thực hiện trên nội dung hoặc request.
 */
enum ModerationAction: string
{
    case Approve = 'approve'; // Duyệt nội dung hoặc request
    case Reject = 'reject';   // Từ chối nội dung hoặc request
    case Hide = 'hide';       // Ẩn nội dung khỏi trang công khai
    case Remove = 'remove';   // Gỡ vĩnh viễn nội dung
    case Restore = 'restore'; // Khôi phục nội dung đã ẩn/gỡ
}
