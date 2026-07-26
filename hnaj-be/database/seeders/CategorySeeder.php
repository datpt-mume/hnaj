<?php

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seed 8 category tham chiếu MVP.
 * Slug là khóa đồng bộ; trạng thái `active`.
 * Khôi phục `deleted_at = null` nếu category từng bị soft-delete.
 */
class CategorySeeder extends Seeder
{
    /**
     * Danh sách category: key là slug, value là tên hiển thị.
     *
     * @var array<string, string>
     */
    protected array $categories = [
        'an-uong' => 'Ăn uống',
        'ca-phe-do-uong' => 'Cà phê & đồ uống',
        'vui-choi-giai-tri' => 'Vui chơi & giải trí',
        'van-hoa-tham-quan' => 'Văn hóa & tham quan',
        'mua-sam' => 'Mua sắm',
        'the-thao-van-dong' => 'Thể thao & vận động',
        'thu-gian-lam-dep' => 'Thư giãn & làm đẹp',
        'thien-nhien-ngoai-troi' => 'Thiên nhiên & ngoài trời',
    ];

    public function run(): void
    {
        foreach ($this->categories as $slug => $name) {
            $category = Category::withTrashed()->firstWhere('slug', $slug);

            if ($category) {
                $category->fill([
                    'name' => $name,
                    'status' => CategoryStatus::Active,
                ])->save();

                if ($category->trashed()) {
                    $category->restore();
                }
            } else {
                Category::create([
                    'name' => $name,
                    'slug' => $slug,
                    'status' => CategoryStatus::Active,
                ]);
            }
        }
    }
}
