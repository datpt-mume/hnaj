<?php

namespace App\Services\PlaceImport;

use InvalidArgumentException;

class PlaceImportOutputValidator
{
    /**
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<string, mixed>  $taxonomy
     * @param  array<string, mixed>  $output
     * @return array<string, array<string, mixed>>
     */
    public function validate(array $records, array $taxonomy, array $output): array
    {
        $results = $output['results'] ?? null;

        if (! is_array($results) || count($results) !== count($records)) {
            throw new InvalidArgumentException('AI output must contain exactly one result per input record.');
        }

        $expectedRefs = array_column($records, 'record_ref');
        $actualRefs = array_map(static fn (mixed $result): mixed => is_array($result) ? ($result['record_ref'] ?? null) : null, $results);

        if ($actualRefs !== $expectedRefs || count(array_unique($actualRefs)) !== count($actualRefs)) {
            throw new InvalidArgumentException('AI output record_ref values do not match the input batch.');
        }

        $categories = $taxonomy['categories'] ?? [];
        $districts = $taxonomy['district_names'] ?? [];
        $tags = $taxonomy['tag_slugs'] ?? [];
        $validated = [];

        foreach ($results as $result) {
            if (! is_array($result) || ! is_bool($result['accepted'] ?? null)) {
                throw new InvalidArgumentException('AI output contains an invalid result.');
            }

            $categorySlug = $result['category_slug'] ?? null;
            $districtName = $result['district_name'] ?? null;
            $tagSlugs = $result['tag_slugs'] ?? [];

            if ($categorySlug !== null && (! is_string($categorySlug) || ! array_key_exists($categorySlug, $categories))) {
                throw new InvalidArgumentException('AI output contains an unknown category.');
            }

            if ($districtName !== null && (! is_string($districtName) || ! in_array($districtName, $districts, true))) {
                throw new InvalidArgumentException('AI output contains an unknown district.');
            }

            if (! is_array($tagSlugs) || array_filter($tagSlugs, static fn (mixed $tag): bool => ! is_string($tag)) !== [] || array_diff($tagSlugs, $tags) !== []) {
                throw new InvalidArgumentException('AI output contains an unknown tag.');
            }

            if ($categorySlug !== null) {
                $allowedTags = $categories[$categorySlug]['allowed_tag_slugs'] ?? [];

                if (array_diff($tagSlugs, $allowedTags) !== []) {
                    throw new InvalidArgumentException('AI output contains a tag incompatible with its category.');
                }
            }

            $openingHours = $this->normalizeOpeningHours($result['opening_hours'] ?? []);

            if (($result['accepted'] ?? false) && ($categorySlug === null || $districtName === null)) {
                throw new InvalidArgumentException('Accepted AI results require category and district.');
            }

            $validated[$result['record_ref']] = [
                'accepted' => $result['accepted'],
                'category_slug' => $categorySlug,
                'tag_slugs' => array_values(array_unique($tagSlugs)),
                'district_name' => $districtName,
                'opening_hours' => $openingHours,
            ];
        }

        return $validated;
    }

    /**
     * @return array<int, array<string, int|string|null>>
     */
    private function normalizeOpeningHours(mixed $openingHours): array
    {
        if (! is_array($openingHours)) {
            throw new InvalidArgumentException('Opening hours must be an array.');
        }

        $normalized = [];

        foreach ($openingHours as $slot) {
            if (! is_array($slot)) {
                throw new InvalidArgumentException('Opening-hour slot must be an object.');
            }

            $day = $slot['day_of_week'] ?? null;
            $type = $slot['schedule_type'] ?? null;
            $opens = $slot['opens_at'] ?? null;
            $closes = $slot['closes_at'] ?? null;

            if (! is_int($day) || $day < 2 || $day > 8 || ! in_array($type, ['regular', 'all_day', 'closed'], true)) {
                throw new InvalidArgumentException('Opening-hour day or schedule type is invalid.');
            }

            if ($type !== 'regular') {
                if ($opens !== null || $closes !== null) {
                    throw new InvalidArgumentException('Non-regular opening hours must not contain times.');
                }

                $normalized[] = [
                    'day_of_week' => $day,
                    'schedule_type' => $type,
                    'opens_at' => null,
                    'closes_at' => null,
                ];

                continue;
            }

            if (! is_string($opens) || ! is_string($closes) || ! $this->isTime($opens) || ! $this->isTime($closes)) {
                throw new InvalidArgumentException('Regular opening hours require valid HH:MM times.');
            }

            $normalized = array_merge($normalized, $this->splitSlot($day, $opens, $closes));
        }

        return $normalized;
    }

    private function isTime(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function splitSlot(int $day, string $opens, string $closes): array
    {
        if ($opens <= $closes) {
            return [[
                'day_of_week' => $day,
                'schedule_type' => 'regular',
                'opens_at' => $opens,
                'closes_at' => $closes,
            ]];
        }

        return [
            [
                'day_of_week' => $day,
                'schedule_type' => 'regular',
                'opens_at' => $opens,
                'closes_at' => '23:59',
            ],
            [
                'day_of_week' => $day === 8 ? 2 : $day + 1,
                'schedule_type' => 'regular',
                'opens_at' => '00:00',
                'closes_at' => $closes,
            ],
        ];
    }
}
