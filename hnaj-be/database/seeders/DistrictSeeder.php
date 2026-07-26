<?php

namespace Database\Seeders;

use App\Enums\DistrictStatus;
use App\Models\District;
use Illuminate\Database\Seeder;

/**
 * Seed 30 đơn vị hành chính cấp quận/huyện/thị xã của Hà Nội.
 * `code` để null theo quyết định MVP; trạng thái `active`.
 * Dùng khóa tự nhiên `name` để chạy lại an toàn.
 */
class DistrictSeeder extends Seeder
{
    /**
     * Danh sách 30 đơn vị: 12 quận, 17 huyện, 1 thị xã.
     *
     * @var array<int, string>
     */
    protected array $districts = [
        // 12 quận
        'Ba Đình',
        'Bắc Từ Liêm',
        'Cầu Giấy',
        'Đống Đa',
        'Hà Đông',
        'Hai Bà Trưng',
        'Hoàn Kiếm',
        'Hoàng Mai',
        'Long Biên',
        'Nam Từ Liêm',
        'Tây Hồ',
        'Thanh Xuân',
        // 17 huyện
        'Ba Vì',
        'Chương Mỹ',
        'Đan Phượng',
        'Đông Anh',
        'Gia Lâm',
        'Hoài Đức',
        'Mê Linh',
        'Mỹ Đức',
        'Phú Xuyên',
        'Phúc Thọ',
        'Quốc Oai',
        'Sóc Sơn',
        'Thạch Thất',
        'Thanh Oai',
        'Thanh Trì',
        'Thường Tín',
        'Ứng Hòa',
        // 1 thị xã
        'Sơn Tây',
    ];

    public function run(): void
    {
        foreach ($this->districts as $name) {
            District::updateOrCreate(
                ['name' => $name],
                [
                    'code' => null,
                    'status' => DistrictStatus::Active,
                ],
            );
        }
    }
}
