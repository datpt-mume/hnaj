<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed dữ liệu tham chiếu: roles, districts, categories, tags, category_tags.
     * Thứ tự gọi theo dependency; mỗi seeder idempotent.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DistrictSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            CategoryTagSeeder::class,
        ]);
    }
}
