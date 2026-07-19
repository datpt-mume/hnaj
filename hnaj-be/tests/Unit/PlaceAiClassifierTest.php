<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\AdministrativeArea;
use App\Models\PlaceImportBatch;
use App\Models\PlaceImportRow;
use App\Models\Tag;
use App\Services\PlaceAiClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PlaceAiClassifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_safe_fields_and_accepts_only_seeded_taxonomy_ids(): void
    {
        $category = Category::create(['name' => 'Ăn uống', 'slug' => 'an-uong']);
        $tag = Tag::create(['name' => 'Lẩu', 'slug' => 'lau', 'group_name' => 'Ẩm thực / loại hình']);
        $district = AdministrativeArea::create(['name' => 'Cầu Giấy', 'slug' => 'cau-giay', 'type' => 'district']);
        $ward = AdministrativeArea::create(['name' => 'Yên Hòa', 'slug' => 'yen-hoa', 'type' => 'ward', 'parent_id' => $district->id]);
        $row = PlaceImportRow::create(['batch_id' => PlaceImportBatch::create(['filename' => 'x.csv'])->id, 'row_number' => 1, 'normalized_data' => ['name' => 'Quán', 'address' => 'Hanoi', 'source_category' => 'Quán ăn', 'emails' => 'secret@example.com']]);

        Http::fake([
            'https://api.ai-box.vn/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'items' => [[
                                'row_id' => $row->id,
                                'category_id' => $category->id,
                                'tag_ids' => [$tag->id],
                                'district_id' => $district->id,
                                'ward_id' => $ward->id,
                            ]],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $result = (new PlaceAiClassifier)->classify([$row]);
        self::assertSame($category->id, $result[0]['category_id']);
        Http::assertSent(fn ($request): bool => ! str_contains($request->body(), 'secret@example.com'));
    }
}
