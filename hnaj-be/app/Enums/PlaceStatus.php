<?php

namespace App\Enums;

/**
 * Trạng thái hiển thị địa điểm.
 * Chỉ có hai trạng thái theo ERD — không có trạng thái "draft" hay "pending".
 */
enum PlaceStatus: string
{
    case Active = 'active'; // Hiển thị công khai trên trang chủ và random
    case Hidden = 'hidden'; // Ẩn khỏi trang công khai, vẫn tồn tại trong database
}
