<?php

namespace Database\Seeders;

use App\Enums\TagStatus;
use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * Seed 24 tag tham chiếu MVP, dùng lại giữa nhiều category.
 * Slug là khóa đồng bộ; trạng thái `active`.
 * Khôi phục `deleted_at = null` nếu tag từng bị soft-delete.
 */
class TagSeeder extends Seeder
{
    /**
     * Danh sách tag: key là slug, value là tên hiển thị.
     *
     * @var array<string, string>
     */
    protected array $tags = [
        // Không khí
        'chill' => 'Chill',
        'cozy' => 'Cozy',
        'soi-dong' => 'Sôi động',
        'yen-tinh' => 'Yên tĩnh',
        'sang-trong' => 'Sang trọng',
        'binh-dan' => 'Bình dân',
        // Dịp đi
        'hen-ho' => 'Hẹn hò',
        'di-nhom' => 'Đi nhóm',
        'gia-dinh' => 'Gia đình',
        'di-mot-minh' => 'Đi một mình',
        // Đối tượng/tiện ích
        'hoc-sinh-sinh-vien' => 'Học sinh — sinh viên',
        'tre-em' => 'Trẻ em',
        'chap-nhan-pet' => 'Chấp nhận pet',
        'co-cho-do-xe' => 'Có chỗ đỗ xe',
        'ngoai-troi' => 'Ngoài trời',
        'trong-nha' => 'Trong nhà',
        // Thời điểm
        'buoi-sang' => 'Buổi sáng',
        'buoi-toi' => 'Buổi tối',
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
