<?php

namespace Database\Seeders;

use App\Services\PlaceImport\CsvPlaceReader;
use App\Services\PlaceImport\OpenAiCompatibleClient;
use App\Services\PlaceImport\PlaceDuplicateDetector;
use App\Services\PlaceImport\PlaceImportOutputValidator;
use App\Services\PlaceImport\PlaceImportPersistence;
use App\Services\PlaceImport\TaxonomyProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlaceCsvImportSeeder extends Seeder
{
    private const BATCH_SIZE = 10;

    /**
     * @var array<string, int>
     */
    private array $stats = [
        'rows' => 0,
        'invalid_rows' => 0,
        'pre_ai_filtered' => 0,
        'duplicates' => 0,
        'batches' => 0,
        'batch_failures' => 0,
        'ai_rejected' => 0,
        'imported' => 0,
        'persistence_failures' => 0,
    ];

    public function __construct(
        private readonly CsvPlaceReader $reader,
        private readonly PlaceDuplicateDetector $duplicates,
        private readonly TaxonomyProvider $taxonomyProvider,
        private readonly OpenAiCompatibleClient $aiClient,
        private readonly PlaceImportOutputValidator $validator,
        private readonly PlaceImportPersistence $persistence,
    ) {}

    public function run(): void
    {
        $taxonomy = $this->taxonomyProvider->get();
        $batch = [];
        $files = glob(database_path('hanoi_Z*.csv')) ?: [];

        sort($files);

        foreach ($files as $file) {
            $skippedBeforeFile = $this->reader->skippedRows();

            foreach ($this->reader->read($file) as $record) {
                $this->stats['rows']++;

                if (! $this->isEligibleForAi($record, $taxonomy)) {
                    $this->stats['pre_ai_filtered']++;

                    continue;
                }

                if ($this->duplicates->isDuplicate($record)) {
                    $this->stats['duplicates']++;

                    continue;
                }

                $batch[] = $record;

                if (count($batch) === self::BATCH_SIZE) {
                    $this->processBatch($batch, $taxonomy);
                    $batch = [];
                }
            }

            $this->stats['invalid_rows'] += $this->reader->skippedRows() - $skippedBeforeFile;
        }

        if ($batch !== []) {
            $this->processBatch($batch, $taxonomy);
        }

        $this->report();
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $taxonomy
     */
    private function isEligibleForAi(array $record, array $taxonomy): bool
    {
        $googlePlaceId = $record['google_place_id'] ?? null;
        $address = $record['address_text'] ?? null;
        $districtNames = $taxonomy['district_names'] ?? [];

        if (! is_string($googlePlaceId) || trim($googlePlaceId) === '' || ! is_string($address)) {
            return false;
        }

        if (mb_stripos($address, 'hà nội', 0, 'UTF-8') !== false) {
            return true;
        }

        foreach ($districtNames as $districtName) {
            if (is_string($districtName) && mb_stripos($address, $districtName, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     * @param  array<string, mixed>  $taxonomy
     */
    private function processBatch(array $batch, array $taxonomy): void
    {
        $this->stats['batches']++;

        try {
            $output = $this->aiClient->classify($batch, $taxonomy);
            $classifications = $this->validator->validate($batch, $taxonomy, $output);
        } catch (Throwable $exception) {
            $this->stats['batch_failures']++;
            Log::warning('Place CSV import batch skipped.', [
                'record_refs' => array_column($batch, 'record_ref'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        foreach ($batch as $record) {
            $classification = $classifications[$record['record_ref']];

            if (! $classification['accepted']) {
                $this->stats['ai_rejected']++;

                continue;
            }

            try {
                $this->persistence->import($record, $classification);
                $this->stats['imported']++;
            } catch (Throwable $exception) {
                $this->stats['persistence_failures']++;
                Log::warning('Place CSV import record failed to persist.', [
                    'record_ref' => $record['record_ref'],
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function report(): void
    {
        Log::info('Place CSV import finished.', $this->stats);

        if ($this->command !== null) {
            $this->command->info('Place CSV import finished: '.json_encode($this->stats, JSON_UNESCAPED_UNICODE));
        }
    }
}
