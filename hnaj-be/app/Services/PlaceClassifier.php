<?php

declare(strict_types=1);

namespace App\Services;

interface PlaceClassifier
{
    /** @return list<array{row_id: string, category_id: string, tag_ids: list<string>, district_id: string, ward_id: string, price_min: ?int, price_max: ?int}> */
    public function classify(iterable $rows): array;
}
