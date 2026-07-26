<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\DistrictStatus;
use App\Enums\RoleName;
use App\Enums\TagStatus;
use App\Models\Category;
use App\Models\CategoryTag;
use App\Models\District;
use App\Models\Role;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_seeds_all_reference_data(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertSame(3, Role::count(), 'Phải có đúng 3 role.');
        $this->assertSame(30, District::count(), 'Phải có đúng 30 district.');
        $this->assertSame(8, Category::count(), 'Phải có đúng 8 category.');
        $this->assertSame(24, Tag::count(), 'Phải có đúng 24 tag.');
    }

    public function test_roles_match_enum_values(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        foreach (RoleName::cases() as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName->value]);
        }
    }

    public function test_districts_have_active_status_and_null_code(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $districts = District::all();
        $this->assertCount(30, $districts);

        foreach ($districts as $district) {
            $this->assertSame(DistrictStatus::Active, $district->status);
            $this->assertNull($district->code);
        }
    }

    public function test_categories_and_tags_have_active_status(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        foreach (Category::all() as $category) {
            $this->assertSame(CategoryStatus::Active, $category->status);
            $this->assertNull($category->deleted_at);
        }

        foreach (Tag::all() as $tag) {
            $this->assertSame(TagStatus::Active, $tag->status);
            $this->assertNull($tag->deleted_at);
        }
    }

    public function test_category_tags_mapping_is_valid_and_unique(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $categoryIds = Category::pluck('id')->all();
        $tagIds = Tag::pluck('id')->all();

        foreach (CategoryTag::all() as $categoryTag) {
            $this->assertContains($categoryTag->category_id, $categoryIds);
            $this->assertContains($categoryTag->tag_id, $tagIds);
        }

        $duplicates = CategoryTag::selectRaw('category_id, tag_id, COUNT(*) as cnt')
            ->groupBy('category_id', 'tag_id')
            ->having('cnt', '>', 1)
            ->get();

        $this->assertCount(0, $duplicates, 'Không được có cặp category_tag trùng.');
    }

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $roleCount = Role::count();
        $districtCount = District::count();
        $categoryCount = Category::count();
        $tagCount = Tag::count();
        $categoryTagCount = CategoryTag::count();

        // Chạy lại lần thứ hai
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertSame($roleCount, Role::count(), 'Role count không đổi khi seed lại.');
        $this->assertSame($districtCount, District::count(), 'District count không đổi khi seed lại.');
        $this->assertSame($categoryCount, Category::count(), 'Category count không đổi khi seed lại.');
        $this->assertSame($tagCount, Tag::count(), 'Tag count không đổi khi seed lại.');
        $this->assertSame($categoryTagCount, CategoryTag::count(), 'CategoryTag count không đổi khi seed lại.');
    }

    public function test_category_seeder_restores_soft_deleted_category(): void
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $category = Category::where('slug', 'an-uong')->first();
        $category->delete();

        $this->assertSoftDeleted($category);

        $this->seed(\Database\Seeders\CategorySeeder::class);

        $category->refresh();
        $this->assertNull($category->deleted_at, 'Category bị soft-delete phải được khôi phục khi seed lại.');
    }

    public function test_tag_seeder_restores_soft_deleted_tag(): void
    {
        $this->seed(\Database\Seeders\TagSeeder::class);

        $tag = Tag::where('slug', 'chill')->first();
        $tag->delete();

        $this->assertSoftDeleted($tag);

        $this->seed(\Database\Seeders\TagSeeder::class);

        $tag->refresh();
        $this->assertNull($tag->deleted_at, 'Tag bị soft-delete phải được khôi phục khi seed lại.');
    }
}
