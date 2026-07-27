<?php

namespace App\Services\PlaceImport;

use App\Enums\PlaceStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\PlaceOpeningHour;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlaceImportPersistence
{
    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed> $classification
     */
    public function import(array $record, array $classification): Place
    {
        $category = Category::query()->where('slug', $classification['category_slug'])->firstOrFail();
        $district = District::query()->where('name', $classification['district_name'])->firstOrFail();
        $tags = Tag::query()->whereIn('slug', $classification['tag_slugs'])->get()->keyBy('slug');

        if ($tags->count() !== count(array_unique($classification['tag_slugs']))) {
            throw new InvalidArgumentException('One or more classified tags no longer exist.');
        }

        return DB::transaction(function () use ($record, $classification, $category, $district, $tags): Place {
            $place = Place::query()->create([
                'name' => $record['name'],
                'address_text' => $record['address_text'],
                'google_place_id' => $record['google_place_id'],
                'phone' => $record['phone'],
                'website_url' => $record['website_url'],
                'google_maps_url' => $record['google_maps_url'],
                'district_id' => $district->id,
                'category_id' => $category->id,
                'latitude' => $record['latitude'],
                'longitude' => $record['longitude'],
                'min_price' => $this->price($record['price_range'])[0],
                'max_price' => $this->price($record['price_range'])[1],
                'description' => $record['description'],
                'status' => PlaceStatus::Active,
                'created_by' => null,
                'thumbnail_image_id' => null,
            ]);

            if ($tags->isNotEmpty()) {
                $place->tags()->attach($tags->pluck('id')->all());
            }

            foreach ($classification['opening_hours'] as $openingHour) {
                $place->openingHours()->create($openingHour);
            }

            $thumbnailUrl = $record['thumbnail_url'];

            if (is_string($thumbnailUrl) && $thumbnailUrl !== '') {
                $image = $place->images()->create([
                    'uploaded_by' => null,
                    'image_url' => $thumbnailUrl,
                    'alt_text' => $record['name'],
                    'is_visible' => true,
                ]);

                $place->update(['thumbnail_image_id' => $image->id]);
            }

            return $place;
        });
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function price(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [null, null];
        }

        preg_match_all('/\d[\d.,]*/', $value, $matches);
        $numbers = array_map(
            fn (string $number): int => $this->parsePriceNumber($number),
            $matches[0],
        );
        $numbers = array_values(array_filter($numbers, static fn (int $number): bool => $number >= 0));

        if ($numbers === []) {
            return [null, null];
        }

        if (count($numbers) === 1) {
            return [$numbers[0], $numbers[0]];
        }

        return [min($numbers), max($numbers)];
    }

    private function parsePriceNumber(string $number): int
    {
        $number = trim($number);

        if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $number) === 1) {
            return (int) str_replace('.', '', $number);
        }

        if (preg_match('/^\d{1,3}(?:,\d{3})+$/', $number) === 1) {
            return (int) str_replace(',', '', $number);
        }

        return (int) round((float) str_replace(',', '.', $number));
    }
}

