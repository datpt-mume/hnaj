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
            'Đồ uống' => ['Cà phê', 'Trà', 'Trà sữa', 'Cocktail', 'Bia thủ công'],
            'Giải trí' => ['Board game', 'Karaoke', 'Rạp chiếu phim', 'Bowling', 'Escape room'],
            'Văn hóa' => ['Triển lãm', 'Workshop', 'Biểu diễn trực tiếp', 'Bảo tàng'],
            'Thể thao' => ['Bắn cung', 'Cầu lông', 'Leo núi trong nhà', 'Chạy bộ', 'Đạp xe'],
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

        $categoryTags = [
            'Ăn uống' => ['Món Việt', 'Món Hàn', 'Món Nhật', 'Món Trung', 'Món Âu', 'Món chay', 'Món nước', 'Đồ nướng', 'Lẩu', 'Ăn vặt', 'Bánh / ngọt', 'Ăn sáng', 'Đặt bàn', 'Giao hàng', 'Mang đi'],
            'Cafe & đồ uống' => ['Cà phê', 'Trà', 'Trà sữa', 'Món nước', 'Bánh / ngọt', 'Yên tĩnh', 'Làm việc', 'Check-in', 'Wifi', 'Ổ điện'],
            'Bar & nightlife' => ['Cocktail', 'Bia thủ công', 'Sôi động', 'Mở khuya', 'Hẹn hò', 'Nhóm đông', 'Biểu diễn trực tiếp'],
            'Ngoài trời' => ['Ngoài trời', 'Chạy bộ', 'Đạp xe', 'Thư giãn', 'Vận động', 'Gia đình', 'Thân thiện thú cưng'],
            'Gaming & giải trí' => ['Board game', 'Karaoke', 'Rạp chiếu phim', 'Bowling', 'Escape room', 'Sôi động', 'Nhóm đông', 'Cặp đôi'],
            'Văn hóa & nghệ thuật' => ['Triển lãm', 'Workshop', 'Biểu diễn trực tiếp', 'Bảo tàng', 'Học hỏi', 'Check-in', 'Yên tĩnh'],
            'Sức khỏe & thư giãn' => ['Bắn cung', 'Cầu lông', 'Leo núi trong nhà', 'Chạy bộ', 'Đạp xe', 'Vận động', 'Thư giãn'],
            'Mua sắm' => ['Check-in', 'Gia đình', 'Đỗ ô tô', 'Đỗ xe máy', 'Tiết kiệm', 'Tầm trung', 'Cao cấp'],
        ];

        foreach ($categoryTags as $categoryName => $tagNames) {
            $category = Category::query()->where('slug', Str::slug($categoryName))->firstOrFail();
            $tagIds = Tag::query()
                ->whereIn('slug', array_map(Str::slug(...), $tagNames))
                ->pluck('id');

            $category->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
