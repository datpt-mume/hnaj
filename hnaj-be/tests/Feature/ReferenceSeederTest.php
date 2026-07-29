<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\DistrictStatus;
use App\Enums\RoleName;
use App\Enums\TagStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Role;
use App\Models\Tag;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_seeds_all_reference_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, Role::count(), 'Phải có đúng 3 role.');
        $this->assertSame(30, District::count(), 'Phải có đúng 30 district.');
        $this->assertSame(8, Category::count(), 'Phải có đúng 8 category.');
        $this->assertSame(17, Tag::query()->where('status', TagStatus::Active)->count(), 'Phải có đúng 17 tag active.');
    }

    public function test_roles_match_enum_values(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (RoleName::cases() as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName->value]);
        }
    }

    public function test_districts_have_active_status_and_null_code(): void
    {
        $this->seed(DatabaseSeeder::class);

        $districts = District::all();
        $this->assertCount(30, $districts);

        foreach ($districts as $district) {
            $this->assertSame(DistrictStatus::Active, $district->status);
            $this->assertNull($district->code);
        }
    }

    public function test_categories_and_tags_have_active_status(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (Category::all() as $category) {
            $this->assertSame(CategoryStatus::Active, $category->status);
            $this->assertNull($category->deleted_at);
        }

        foreach (Tag::all() as $tag) {
            $this->assertSame(TagStatus::Active, $tag->status);
            $this->assertNull($tag->deleted_at);
        }
    }

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $roleCount = Role::count();
        $districtCount = District::count();
        $categoryCount = Category::count();
        $tagCount = Tag::count();

        // Chạy lại lần thứ hai
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($roleCount, Role::count(), 'Role count không đổi khi seed lại.');
        $this->assertSame($districtCount, District::count(), 'District count không đổi khi seed lại.');
        $this->assertSame($categoryCount, Category::count(), 'Category count không đổi khi seed lại.');
        $this->assertSame($tagCount, Tag::count(), 'Tag count không đổi khi seed lại.');
    }

    public function test_category_seeder_restores_soft_deleted_category(): void
    {
        $this->seed(CategorySeeder::class);

        $category = Category::where('slug', 'an-uong')->first();
        $category->delete();

        $this->assertSoftDeleted($category);

        $this->seed(CategorySeeder::class);

        $category->refresh();
        $this->assertNull($category->deleted_at, 'Category bị soft-delete phải được khôi phục khi seed lại.');
    }

    public function test_tag_seeder_restores_soft_deleted_tag(): void
    {
        $this->seed(TagSeeder::class);

        $tag = Tag::where('slug', 'chill')->first();
        $tag->delete();

        $this->assertSoftDeleted($tag);

        $this->seed(TagSeeder::class);

        $tag->refresh();
        $this->assertNull($tag->deleted_at, 'Tag bị soft-delete phải được khôi phục khi seed lại.');
    }
}
