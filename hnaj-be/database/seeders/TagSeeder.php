<?php

namespace Database\Seeders;

use App\Enums\TagStatus;
use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * Seed các tag tham chiếu cốt lõi, độc lập với category.
 * Slug là khóa đồng bộ; trạng thái `active`.
 * Khôi phục `deleted_at = null` nếu tag từng bị soft-delete.
 */
class TagSeeder extends Seeder
{
    /**
     * Các tag seed cũ được ngừng sử dụng nhưng vẫn giữ record để bảo toàn place_tags hiện có.
     *
     * @var array<int, string>
     */
    protected array $retiredTagSlugs = [
        'cozy',
        'soi-dong',
        'binh-dan',
        'di-mot-minh',
        'trong-nha',
        'buoi-sang',
        'buoi-toi',
    ];

    /**
     * Danh sách tag: key là slug, value là tên hiển thị.
     *
     * @var array<string, string>
     */
    protected array $tags = [
        // Không khí
        'chill' => 'Chill',
        'yen-tinh' => 'Yên tĩnh',
        'sang-trong' => 'Sang trọng',
        // Dịp đi
        'hen-ho' => 'Hẹn hò',
        'di-nhom' => 'Đi nhóm',
        'gia-dinh' => 'Gia đình',
        // Đối tượng/tiện ích
        'hoc-sinh-sinh-vien' => 'Học sinh — sinh viên',
        'tre-em' => 'Trẻ em',
        'chap-nhan-pet' => 'Chấp nhận pet',
        'co-cho-do-xe' => 'Có chỗ đỗ xe',
        'ngoai-troi' => 'Ngoài trời',
        // Thời điểm
        'mo-khuya' => 'Mở khuya',
        // Đặc trưng ăn uống
        'do-an-duong-pho' => 'Đồ ăn đường phố',
        'an-nhanh' => 'Ăn nhanh',
        'do-chay' => 'Đồ chay',
        'do-ngot' => 'Đồ ngọt',
        'bia-nhau' => 'Bia & nhậu',
    ];

    public function run(): void
    {
        Tag::query()
            ->whereIn('slug', $this->retiredTagSlugs)
            ->update(['status' => TagStatus::Inactive]);

        foreach ($this->tags as $slug => $name) {
            $tag = Tag::withTrashed()->firstWhere('slug', $slug);

            if ($tag) {
                $tag->fill([
                    'name' => $name,
                    'status' => TagStatus::Active,
                ])->save();

                if ($tag->trashed()) {
                    $tag->restore();
                }
            } else {
                Tag::create([
                    'name' => $name,
                    'slug' => $slug,
                    'status' => TagStatus::Active,
                ]);
            }
        }
    }
}
