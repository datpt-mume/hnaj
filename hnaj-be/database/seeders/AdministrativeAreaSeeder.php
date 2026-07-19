<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdministrativeArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class AdministrativeAreaSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            'Ba Đình' => ['Phúc Xá', 'Trúc Bạch', 'Vĩnh Phúc', 'Cống Vị', 'Liễu Giai', 'Ngọc Hà', 'Đội Cấn', 'Kim Mã', 'Giảng Võ', 'Thành Công'],
            'Cầu Giấy' => ['Nghĩa Đô', 'Nghĩa Tân', 'Mai Dịch', 'Dịch Vọng', 'Dịch Vọng Hậu', 'Quan Hoa', 'Yên Hòa', 'Trung Hòa'],
            'Đống Đa' => ['Cát Linh', 'Văn Miếu', 'Quốc Tử Giám', 'Láng Thượng', 'Láng Hạ', 'Ô Chợ Dừa', 'Hàng Bột', 'Nam Đồng', 'Quang Trung', 'Trung Liệt', 'Thổ Quan', 'Khâm Thiên', 'Phương Liên', 'Phương Mai', 'Kim Liên'],
            'Hai Bà Trưng' => ['Nguyễn Du', 'Bùi Thị Xuân', 'Ngô Thì Nhậm', 'Phạm Đình Hổ', 'Đồng Nhân', 'Phố Huế', 'Đồng Tâm', 'Bạch Mai', 'Quỳnh Mai', 'Vĩnh Tuy'],
            'Hoàn Kiếm' => ['Phúc Tân', 'Đồng Xuân', 'Hàng Mã', 'Hàng Buồm', 'Hàng Đào', 'Hàng Bạc', 'Hàng Gai', 'Hàng Trống', 'Cửa Nam', 'Tràng Tiền'],
            'Thanh Xuân' => ['Nhân Chính', 'Thượng Đình', 'Khương Trung', 'Khương Mai', 'Khương Đình', 'Thanh Xuân Bắc', 'Thanh Xuân Nam', 'Hạ Đình', 'Kim Giang'],
            'Tây Hồ' => ['Phú Thượng', 'Nhật Tân', 'Tứ Liên', 'Quảng An', 'Xuân La', 'Yên Phụ', 'Bưởi', 'Thụy Khuê'],
            'Hà Đông' => ['Nguyễn Trãi', 'Mộ Lao', 'Văn Quán', 'Vạn Phúc', 'Yết Kiêu', 'Quang Trung', 'Phúc La', 'La Khê', 'Dương Nội', 'Yên Nghĩa', 'Phú La', 'Kiến Hưng', 'Phú Lương'],
            'Long Biên' => ['Bồ Đề', 'Ngọc Lâm', 'Gia Thụy', 'Ngọc Thụy', 'Thượng Thanh', 'Đức Giang', 'Việt Hưng', 'Phúc Đồng', 'Sài Đồng', 'Long Biên'],
            'Hoàng Mai' => ['Hoàng Văn Thụ', 'Mai Động', 'Tương Mai', 'Giáp Bát', 'Tân Mai', 'Thịnh Liệt', 'Định Công', 'Đại Kim', 'Thanh Trì', 'Vĩnh Hưng', 'Yên Sở', 'Lĩnh Nam'],
        ];

        foreach ($districts as $districtName => $wards) {
            $district = AdministrativeArea::updateOrCreate(
                ['slug' => Str::slug($districtName).'-ha-noi'],
                ['name' => $districtName, 'type' => 'district', 'city' => 'Hà Nội', 'is_active' => true],
            );
            foreach ($wards as $wardName) {
                AdministrativeArea::updateOrCreate(
                    ['slug' => Str::slug($wardName).'-'.Str::slug($districtName).'-ha-noi'],
                    ['name' => $wardName, 'type' => 'ward', 'parent_id' => $district->id, 'city' => 'Hà Nội', 'is_active' => true],
                );
            }
        }
    }
}
