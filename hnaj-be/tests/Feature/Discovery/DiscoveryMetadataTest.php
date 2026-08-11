<?php

namespace Tests\Feature\Discovery;

use App\Enums\CategoryStatus;
use App\Enums\DistrictStatus;
use App\Enums\TagStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_returns_active_discovery_metadata(): void
    {
        $activeCategory = Category::factory()->create([
            'name' => 'Ăn uống',
            'slug' => 'an-uong',
            'status' => CategoryStatus::Active,
        ]);
        Category::factory()->create(['status' => CategoryStatus::Inactive]);

        $activeDistrict = District::factory()->create([
            'name' => 'Ba Đình',
            'code' => null,
            'status' => DistrictStatus::Active,
        ]);
        District::factory()->create(['status' => DistrictStatus::Inactive]);

        $activeTag = Tag::factory()->create([
            'name' => 'Chill',
            'slug' => 'chill',
            'status' => TagStatus::Active,
        ]);
        $deletedTag = Tag::factory()->create(['status' => TagStatus::Active]);
        $deletedTag->delete();

        $response = $this->getJson('/api/meta/discovery');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.categories.0.id', $activeCategory->id)
            ->assertJsonPath('data.categories.0.name', 'Ăn uống')
            ->assertJsonPath('data.districts.0.id', $activeDistrict->id)
            ->assertJsonPath('data.districts.0.name', 'Ba Đình')
            ->assertJsonPath('data.tags.0.id', $activeTag->id)
            ->assertJsonPath('data.tags.0.name', 'Chill');

        $response->assertJsonCount(1, 'data.categories');
        $response->assertJsonCount(1, 'data.districts');
        $response->assertJsonCount(1, 'data.tags');
    }

    public function test_metadata_is_sorted_by_name(): void
    {
        Category::factory()->create(['name' => 'Zeta', 'slug' => 'zeta']);
        Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        District::factory()->create(['name' => 'Zeta']);
        District::factory()->create(['name' => 'Alpha']);
        Tag::factory()->create(['name' => 'Zeta', 'slug' => 'zeta']);
        Tag::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);

        $response = $this->getJson('/api/meta/discovery')->assertOk();

        $response
            ->assertJsonPath('data.categories.0.name', 'Alpha')
            ->assertJsonPath('data.districts.0.name', 'Alpha')
            ->assertJsonPath('data.tags.0.name', 'Alpha');
    }
}
