<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiscoveryOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tags_are_limited_to_the_selected_category_taxonomy(): void
    {
        $this->seed(TaxonomySeeder::class);

        $this->getJson('/api/v1/tags?category=gaming-giai-tri')
            ->assertOk()
            ->assertJsonPath('data.tags.0.slug', 'board-game')
            ->assertJsonMissing(['slug' => 'mon-viet']);
    }
}