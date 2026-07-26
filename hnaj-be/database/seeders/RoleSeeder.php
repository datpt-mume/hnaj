<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seed 3 role hệ thống: user, sub_admin, admin.
 * Dùng khóa tự nhiên `name` để chạy lại an toàn.
 */
class RoleSeeder extends Seeder
{
    /**
     * Danh sách role tham chiếu.
     * Khóa mảng là giá trị RoleName, value là mô tả tiếng Việt.
     *
     * @var array<string, string>
     */
    protected array $roles = [
        RoleName::User->value => 'Người dùng thông thường, có thể bookmark, review, comment.',
        RoleName::SubAdmin->value => 'Quản lý địa điểm, có quyền quản lý place được gán.',
        RoleName::Admin->value => 'Quản trị viên, có toàn quyền hệ thống.',
    ];

    public function run(): void
    {
        foreach ($this->roles as $name => $description) {
            Role::updateOrCreate(
                ['name' => $name],
                ['description' => $description],
            );
        }
    }
}
