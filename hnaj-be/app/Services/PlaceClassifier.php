<?php

declare(strict_types=1);

namespace App\Services;

interface PlaceClassifier
{
    /** @return list<array{row_id: string, category_id: string, tag_ids: list<string>}> */
    public function classify(iterable $rows): array;
}
