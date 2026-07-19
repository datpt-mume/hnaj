<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Ăn uống', 'Cafe & đồ uống', 'Bar & nightlife', 'Ngoài trời',
            'Gaming & giải trí', 'Văn hóa & nghệ thuật', 'Sức khỏe & thư giãn', 'Mua sắm',
        ];

        foreach ($categories as $sortOrder => $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $sortOrder],
            );
        }

        $groups = [
            'Ẩm thực / loại hình' => ['Món Việt', 'Món Hàn', 'Món Nhật', 'Món Trung', 'Món Âu', 'Món chay', 'Món nước', 'Đồ nướng', 'Lẩu', 'Ăn vặt', 'Bánh / ngọt'],
            'Không khí' => ['Yên tĩnh', 'Sôi động', 'Lãng mạn', 'Riêng tư', 'Bình dân', 'Sang trọng'],
            'Tiện ích' => ['Wifi', 'Ổ điện', 'Điều hòa', 'Ngoài trời', 'Đỗ ô tô', 'Đỗ xe máy', 'Thân thiện thú cưng'],
            'Phù hợp nhóm' => ['Đi một mình', 'Cặp đôi', 'Gia đình', 'Trẻ em', 'Nhóm đông'],
            'Thời gian & dịch vụ' => ['Mở khuya', 'Mở 24/7', 'Ăn sáng', 'Đặt bàn', 'Giao hàng', 'Mang đi'],
            'Mức giá' => ['Tiết kiệm', 'Tầm trung', 'Cao cấp'],
            'Trải nghiệm' => ['Hẹn hò', 'Làm việc', 'Check-in', 'Thư giãn', 'Vận động', 'Học hỏi'],
            'Khả năng tiếp cận' => ['Lối đi xe lăn', 'Chỗ ngồi tiếp cận', 'Nhà vệ sinh tiếp cận'],
        ];

        $sortOrder = 0;
        foreach ($groups as $groupName => $names) {
            foreach ($names as $name) {
                Tag::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'name' => $name,
                        'group_name' => $groupName,
                        'is_active' => true,
                        'sort_order' => $sortOrder++,
                    ],
                );
            }
        }
    }
}
