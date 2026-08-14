<?php

namespace App\Actions\Admin\Place;

use App\Models\Place;

/**
 * Xóa mềm place: ẩn khỏi mọi truy vấn người dùng, giữ nguyên dữ liệu
 * và quan hệ để có thể khôi phục. Không xóa dữ liệu liên quan.
 */
class SoftDeletePlace
{
    public function handle(Place $place): void
    {
        $place->delete();
    }
}
