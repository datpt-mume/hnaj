<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Place;
use App\Models\PlaceExternalId;
use App\Models\PlaceImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class PlaceImportService
{
    public function __construct(private readonly PlaceClassifier $classifier) {}

    public function confirm(PlaceImportBatch $batch): PlaceImportBatch
    {
        if (! in_array($batch->status, ['previewed'], true)) {
            throw new \RuntimeException('Chỉ batch previewed mới được confirm.');
        }

        $batch->update(['status' => 'queued', 'confirmed_at' => now(), 'error_message' => null]);

        return $batch->fresh('rows');
    }
}
