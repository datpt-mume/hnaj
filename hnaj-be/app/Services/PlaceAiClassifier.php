<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdministrativeArea;
use App\Models\Category;
use App\Models\PlaceImportRow;
use App\Models\Tag;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PlaceAiClassifier implements PlaceClassifier
{
    /** @return list<array{row_id: string, category_id: string, tag_ids: list<string>, district_id: string, ward_id: string}> */
    public function classify(iterable $rows): array
    {
        $rows = collect($rows)->values();
        if ($rows->isEmpty() || $rows->count() > 10) {
            throw new RuntimeException('AI classification batch phải có từ 1 đến 10 dòng.');
        }

        $categories = Category::query()->where('is_active', true)->get(['id', 'name', 'slug']);
        $tags = Tag::query()->where('is_active', true)->get(['id', 'name', 'slug', 'group_name']);
        $areas = AdministrativeArea::query()->where('is_active', true)->get(['id', 'name', 'slug', 'type', 'parent_id']);
        $payload = $rows->map(fn (PlaceImportRow $row): array => [
            'row_id' => $row->id,
            'name' => $row->normalized_data['name'] ?? null,
            'address' => $row->normalized_data['address'] ?? null,
            'source_category' => $row->normalized_data['source_category'] ?? null,
            'complete_address' => $row->normalized_data['complete_address'] ?? null,
        ])->all();

        $response = Http::baseUrl(config('services.ai_box.base_url'))
            ->withToken((string) config('services.ai_box.api_key'))
            ->timeout((int) config('services.ai_box.timeout', 60))
            ->retry(3, 250)
            ->post('/chat/completions', [
                'model' => config('services.ai_box.model'),
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'Classify each place into exactly one category_id, zero or more tag_ids, exactly one district_id, and exactly one ward_id. Use the address and complete_address supplied for each place. Return JSON object {"items":[{"row_id":"...","category_id":"...","tag_ids":[],"district_id":"...","ward_id":"..."}]}. Use only IDs from the supplied taxonomy and administrative areas. Never invent, omit, or return names instead of IDs. The district_id must be the parent of ward_id. This classification is authoritative for import; if the location cannot be mapped, return the closest valid administrative area from the supplied list based on the address.'],
                    ['role' => 'user', 'content' => json_encode(['taxonomy' => ['categories' => $categories, 'tags' => $tags, 'administrative_areas' => $areas], 'places' => $payload], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)],
                ],
            ])->throw();

        $content = data_get($response->json(), 'choices.0.message.content');
        $decoded = json_decode((string) $content, true);
        $items = is_array($decoded) ? ($decoded['items'] ?? null) : null;
        if (! is_array($items) || count($items) !== $rows->count()) {
            throw new RuntimeException('AI response không đúng schema classification.');
        }

        $categoryIds = $categories->pluck('id')->all();
        $tagIds = $tags->pluck('id')->all();
        $areaIds = $areas->pluck('id')->all();
        $areaParents = $areas->pluck('parent_id', 'id')->all();
        foreach ($items as $item) {
            if (! is_array($item) || ! in_array($item['category_id'] ?? null, $categoryIds, true)
                || ! is_array($item['tag_ids'] ?? null) || array_diff($item['tag_ids'], $tagIds) !== []
                || ! in_array($item['district_id'] ?? null, $areaIds, true)
                || ! in_array($item['ward_id'] ?? null, $areaIds, true)
                || ($areaParents[$item['ward_id']] ?? null) !== $item['district_id']) {
                throw new RuntimeException('AI trả về taxonomy ID không hợp lệ.');
            }
        }

        return $items;
    }
}
