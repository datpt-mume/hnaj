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
            throw new InvalidArgumentException('results_count_mismatch: AI output must contain exactly one result per input record.');
        }

        $expectedRefs = array_column($records, 'record_ref');
        $actualRefs = array_map(
            static fn (mixed $result): mixed => is_array($result) ? ($result['record_ref'] ?? null) : null,
            $results,
        );

        if ($actualRefs !== $expectedRefs || count(array_unique($actualRefs)) !== count($actualRefs)) {
            throw new InvalidArgumentException('record_ref_mismatch: AI output record_ref values do not match the input batch.');
        }

        $taxonomyIndex = $this->taxonomyIndex($taxonomy);
        $validated = [];

        foreach ($results as $result) {
            $recordRef = $result['record_ref'];

            if (! is_bool($result['error'] ?? null)) {
                $validated[$recordRef] = $this->errorResult('invalid_error_flag');

                continue;
            }

            if ($result['error']) {
                $validated[$recordRef] = $this->errorResult($this->errorReason($result['error_reason'] ?? null));

                continue;
            }

            try {
                $validated[$recordRef] = $this->validateAcceptedResult($result, $taxonomyIndex);
            } catch (InvalidArgumentException $exception) {
                $validated[$recordRef] = $this->errorResult($this->exceptionReason($exception));
            }
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array{categories: array<int, true>, districts: array<int, true>, tags: array<int, true>}  $taxonomyIndex
     * @return array<string, mixed>
     */
    private function validateAcceptedResult(array $result, array $taxonomyIndex): array
    {
        $categoryId = $result['category_id'] ?? null;
        $districtId = $result['district_id'] ?? null;
        $tagIds = $result['tag_ids'] ?? [];
        $normalizedAddress = $result['normalized_address'] ?? null;

        if (! is_int($categoryId) || ! isset($taxonomyIndex['categories'][$categoryId])) {
            throw new InvalidArgumentException('unknown_category: AI output contains an unknown category.');
        }

        if (! is_int($districtId) || ! isset($taxonomyIndex['districts'][$districtId])) {
            throw new InvalidArgumentException('unknown_district: AI output contains an unknown district.');
        }

        if (! is_array($tagIds) || array_filter($tagIds, static fn (mixed $tagId): bool => ! is_int($tagId)) !== []) {
            throw new InvalidArgumentException('invalid_tag_ids: AI output tag_ids must be an integer array.');
        }

        $tagIds = array_values(array_unique($tagIds));

        foreach ($tagIds as $tagId) {
            if (! isset($taxonomyIndex['tags'][$tagId])) {
                throw new InvalidArgumentException('unknown_tag: AI output contains an unknown tag.');
            }
        }

        if (! is_string($normalizedAddress) || trim($normalizedAddress) === '') {
            throw new InvalidArgumentException('invalid_normalized_address: AI output must contain a normalized address.');
        }

        return [
            'error' => false,
            'error_reason' => null,
            'normalized_address' => mb_substr(trim($normalizedAddress), 0, 1000),
            'category_id' => $categoryId,
            'tag_ids' => $tagIds,
            'district_id' => $districtId,
            'opening_hours' => $this->normalizeOpeningHours($result['opening_hours'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $taxonomy
     * @return array{categories: array<int, true>, districts: array<int, true>, tags: array<int, true>}
     */
    private function taxonomyIndex(array $taxonomy): array
    {
        $index = [
            'categories' => [],
            'districts' => [],
            'tags' => [],
        ];

        foreach ($taxonomy['categories'] ?? [] as $category) {
            if (! is_array($category) || ! is_int($category['id'] ?? null)) {
                continue;
            }

            $index['categories'][$category['id']] = true;
        }

        foreach ($taxonomy['districts'] ?? [] as $district) {
            if (is_array($district) && is_int($district['id'] ?? null)) {
                $index['districts'][$district['id']] = true;
            }
        }

        foreach ($taxonomy['tags'] ?? [] as $tag) {
            if (is_array($tag) && is_int($tag['id'] ?? null)) {
                $index['tags'][$tag['id']] = true;
            }
        }

        return $index;
    }

    /**
     * @return array<string, mixed>
     */
    private function errorResult(string $reason): array
    {
        return [
            'error' => true,
            'error_reason' => $reason,
            'normalized_address' => null,
            'category_id' => null,
            'tag_ids' => [],
            'district_id' => null,
            'opening_hours' => [],
        ];
    }

    private function errorReason(mixed $reason): string
    {
        if (! is_string($reason) || trim($reason) === '') {
            return 'ai_rejected';
        }

        return mb_substr(trim($reason), 0, 255);
    }

    private function exceptionReason(InvalidArgumentException $exception): string
    {
        return str_contains($exception->getMessage(), ':')
            ? (string) strstr($exception->getMessage(), ':', true)
            : 'invalid_result';
    }

    /**
     * @return array<int, array<string, int|string|null>>
     */
    private function normalizeOpeningHours(mixed $openingHours): array
    {
        if (! is_array($openingHours)) {
            throw new InvalidArgumentException('opening_hours_shape: Opening hours must be an array.');
        }

        $normalized = [];

        foreach ($openingHours as $slot) {
            if (! is_array($slot)) {
                throw new InvalidArgumentException('opening_hours_slot_shape: Opening-hour slot must be an object.');
            }

            $day = $slot['day_of_week'] ?? null;
            $type = $slot['schedule_type'] ?? null;
            $opens = $slot['opens_at'] ?? null;
            $closes = $slot['closes_at'] ?? null;

            if (! is_int($day) || $day < 2 || $day > 8 || ! in_array($type, ['regular', 'all_day', 'closed'], true)) {
                throw new InvalidArgumentException('opening_hours_day_or_type: Opening-hour day or schedule type is invalid.');
            }

            if ($type !== 'regular') {
                if ($opens !== null || $closes !== null) {
                    throw new InvalidArgumentException('opening_hours_non_regular_times: Non-regular opening hours must not contain times.');
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
                throw new InvalidArgumentException('opening_hours_time: Regular opening hours require valid HH:MM times.');
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
