<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTag;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed mapping category_tags theo taxonomy MVP đã duyệt.
 * Tra cứu ID bằng slug để dễ review; kiểm tra thiếu dữ liệu cha.
 * Đồng bộ các cặp mapping mà không tạo bản ghi trùng.
 * Không xóa mapping ngoài allowlist để tránh phá dữ liệu quản trị viên thêm.
 */
class CategoryTagSeeder extends Seeder
{
    /**
     * Mapping: key là category slug, value là mảng tag slug được phép.
     *
     * @var array<string, array<int, string>>
     */
    protected array $mapping = [
        'an-uong' => [
            'chill', 'cozy', 'soi-dong', 'yen-tinh', 'sang-trong', 'binh-dan',
            'hen-ho', 'di-nhom', 'gia-dinh', 'di-mot-minh',
            'hoc-sinh-sinh-vien', 'chap-nhan-pet', 'co-cho-do-xe', 'ngoai-troi', 'trong-nha',
            'buoi-sang', 'buoi-toi', 'mo-khuya',
            'do-an-duong-pho', 'an-nhanh', 'do-chay', 'do-ngot', 'bia-nhau',
        ],
        'ca-phe-do-uong' => [
            'chill', 'cozy', 'soi-dong', 'yen-tinh', 'sang-trong', 'binh-dan',
            'hen-ho', 'di-nhom', 'gia-dinh', 'di-mot-minh',
            'hoc-sinh-sinh-vien', 'chap-nhan-pet', 'co-cho-do-xe', 'ngoai-troi', 'trong-nha',
            'buoi-sang', 'buoi-toi', 'mo-khuya',
            'do-chay', 'do-ngot',
        ],
        'vui-choi-giai-tri' => [
            'chill', 'soi-dong', 'sang-trong', 'binh-dan',
            'hen-ho', 'di-nhom', 'gia-dinh', 'di-mot-minh',
            'hoc-sinh-sinh-vien', 'tre-em', 'chap-nhan-pet', 'co-cho-do-xe', 'ngoai-troi', 'trong-nha',
            'buoi-toi', 'mo-khuya',
        ],
        'van-hoa-tham-quan' => [
            'chill', 'yen-tinh', 'binh-dan',
            'hen-ho', 'di-nhom', 'gia-dinh', 'di-mot-minh',
            'hoc-sinh-sinh-vien', 'tre-em', 'co-cho-do-xe', 'ngoai-troi', 'trong-nha',
            'buoi-sang', 'buoi-toi',
        ],
        'mua-sam' => [
            'soi-dong', 'sang-trong', 'binh-dan',
            'hen-ho', 'di-nhom', 'gia-dinh', 'di-mot-minh',
            'hoc-sinh-sinh-vien', 'tre-em', 'chap-nhan-pet', 'co-cho-do-xe', 'ngoai-troi', 'trong-nha',
            'buoi-sang', 'buoi-toi', 'mo-khuya',
        ],
        'the-thao-van-dong' => [
            'soi-dong', 'yen-tinh', 'binh-dan',
            'di-nhom', 'gia-dinh', 'di-mot-minh',
            'hoc-sinh-sinh-vien', 'tre-em', 'co-cho-do-xe', 'ngoai-troi', 'trong-nha',
            'buoi-sang', 'buoi-toi',
        ],
        'thu-gian-lam-dep' => [
            'chill', 'cozy', 'yen-tinh', 'sang-trong', 'binh-dan',
            'hen-ho', 'di-nhom', 'di-mot-minh',
            'co-cho-do-xe', 'ngoai-troi', 'trong-nha',
            'buoi-sang', 'buoi-toi', 'mo-khuya',
        ],
        'thien-nhien-ngoai-troi' => [
            'chill', 'yen-tinh', 'binh-dan',
            'hen-ho', 'di-nhom', 'gia-dinh', 'di-mot-minh',
            'hoc-sinh-sinh-vien', 'tre-em', 'chap-nhan-pet', 'co-cho-do-xe', 'ngoai-troi',
            'buoi-sang', 'buoi-toi',
        ],
    ];

    public function run(): void
    {
        $categoryIds = Category::pluck('id', 'slug');
        $tagIds = Tag::pluck('id', 'slug');

        $missingCategories = array_diff(array_keys($this->mapping), $categoryIds->keys()->all());
        $missingTags = array_diff(array_unique(array_merge(...array_values($this->mapping))), $tagIds->keys()->all());

        if ($missingCategories || $missingTags) {
            throw new \RuntimeException(sprintf(
                'Thiếu dữ liệu cha để seed category_tags. Thiếu category: [%s]. Thiếu tag: [%s].',
                implode(', ', $missingCategories),
                implode(', ', $missingTags),
            ));
        }

        $existing = DB::table('category_tags')
            ->get(['category_id', 'tag_id'])
            ->map(fn ($row) => "{$row->category_id}:{$row->tag_id}")
            ->all();

        $now = now();
        $rows = [];

        foreach ($this->mapping as $categorySlug => $tagSlugs) {
            $categoryId = $categoryIds[$categorySlug];

            foreach ($tagSlugs as $tagSlug) {
                $tagId = $tagIds[$tagSlug];
                $key = "{$categoryId}:{$tagId}";

                if (in_array($key, $existing, true)) {
                    continue;
                }

                $rows[] = [
                    'category_id' => $categoryId,
                    'tag_id' => $tagId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $existing[] = $key;
            }
        }

        if ($rows) {
            Schema::disableForeignKeyConstraints();
            try {
                DB::table('category_tags')->insert($rows);
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        }
    }
}
