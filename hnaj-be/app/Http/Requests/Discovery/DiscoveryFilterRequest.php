<?php

namespace App\Http\Requests\Discovery;

use App\Actions\Discovery\DiscoveryFilters;
use App\Repositories\PlaceRepository;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation cho endpoint POST /api/discovery/random.
 *
 * Tất cả field đều tùy chọn. lat/lng phải đi cặp; radius_km chỉ có ý nghĩa
 * khi có tọa độ. excluded_place_ids bị giới hạn để tránh query khổng lồ.
 */
class DiscoveryFilterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'tag_ids' => ['nullable', 'array', 'max:20'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'open_now' => ['nullable', 'boolean'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'radius_km' => ['nullable', 'numeric', 'between:0.5,50'],
            'excluded_place_ids' => [
                'nullable',
                'array',
                'max:'.PlaceRepository::MAX_EXCLUDED_IDS,
            ],
            'excluded_place_ids.*' => ['integer', 'exists:places,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        if (array_key_exists('open_now', $input) && $input['open_now'] !== null) {
            $input['open_now'] = $this->normalizeBoolean($input['open_now']);
        }

        if (($input['lat'] ?? null) !== null && ($input['radius_km'] ?? null) === null) {
            // Mặc định 5km khi có tọa độ mà client không gửi radius
            // (docs/prd.md §5.1).
            $input['radius_km'] = 5.0;
        }

        $this->merge($input);
    }

    public function filters(): DiscoveryFilters
    {
        // Dùng `?? default` thay vì `validated(key, default)`: data_get chỉ áp
        // dụng default khi key vắng mặt, nên payload gửi null tường minh
        // (vd {"excluded_place_ids": null}) sẽ trả về null và gây TypeError.
        return new DiscoveryFilters(
            categoryId: $this->validated('category_id'),
            districtId: $this->validated('district_id'),
            minPrice: $this->validated('min_price'),
            maxPrice: $this->validated('max_price'),
            tagIds: array_map(
                static fn (mixed $id): int => (int) $id,
                $this->validated('tag_ids') ?? [],
            ),
            openNow: $this->validated('open_now') ?? true,
            latitude: $this->validated('lat') !== null
                ? (float) $this->validated('lat')
                : null,
            longitude: $this->validated('lng') !== null
                ? (float) $this->validated('lng')
                : null,
            radiusKm: $this->validated('radius_km') !== null
                ? (float) $this->validated('radius_km')
                : null,
            excludedPlaceIds: array_map(
                static fn (mixed $id): int => (int) $id,
                $this->validated('excluded_place_ids') ?? [],
            ),
            userId: $this->resolveUserId(),
        );
    }

    /**
     * Resolve the user id when the request carries a valid bearer token.
     *
     * The discovery endpoint is public, so there is no `auth:sanctum`
     * middleware; the default guard is `web` (session) and always returns null
     * for API tokens. Therefore the `sanctum` guard is queried explicitly.
     * Guests resolve to null and the two personalization criteria (bookmark,
     * "go there" visit) do not take part in ranking.
     */
    private function resolveUserId(): ?int
    {
        $user = $this->user('sanctum');

        return $user?->id !== null ? (int) $user->id : null;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true);
    }
}
