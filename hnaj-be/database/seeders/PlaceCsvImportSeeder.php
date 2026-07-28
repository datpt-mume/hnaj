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
use RuntimeException;
use Throwable;

class PlaceCsvImportSeeder extends Seeder
{
    private const BATCH_SIZE = 10;

    /**
     * @var array<string, int>
     */
    private array $stats = [];

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
        $this->resetState();
        $taxonomy = $this->taxonomyProvider->get();
        $taxonomySummary = $this->taxonomySummary($taxonomy);

        if ($taxonomySummary['empty_sections'] !== []) {
            Log::error('Place CSV import taxonomy is incomplete.', $taxonomySummary);

            throw new RuntimeException('Place CSV import requires active categories, tags, and districts.');
        }

        /** @var array<string, array<string, mixed>> $sourceRecordsByRef */
        $sourceRecordsByRef = [];
        $eligibleRecordRefs = [];

        foreach ($this->csvFiles() as $file) {
            $skippedBeforeFile = $this->reader->skippedRows();

            foreach ($this->reader->read($file) as $record) {
                $this->stats['rows']++;
                $googlePlaceId = $record['import_data']['google_place_id'];

                if ($this->duplicates->isDuplicate($googlePlaceId)) {
                    $this->stats['duplicates']++;

                    continue;
                }

                $recordRef = $record['record_ref'];
                $sourceRecordsByRef[$recordRef] = $record;
                $eligibleRecordRefs[] = $recordRef;
            }

            $this->stats['invalid_rows'] += $this->reader->skippedRows() - $skippedBeforeFile;
        }

        foreach (array_chunk($eligibleRecordRefs, self::BATCH_SIZE) as $recordRefs) {
            $batch = array_map(
                static fn (string $recordRef): array => $sourceRecordsByRef[$recordRef]['ai_data'],
                $recordRefs,
            );

            $this->processBatch($batch, $sourceRecordsByRef, $taxonomy);
        }

        $this->report();
    }

    /**
     * @return array<int, string>
     */
    protected function csvFiles(): array
    {
        $files = glob(database_path('hanoi_Z*.csv')) ?: [];
        sort($files);

        return $files;
    }

    private function resetState(): void
    {
        $this->stats = [
            'rows' => 0,
            'invalid_rows' => 0,
            'duplicates' => 0,
            'batches' => 0,
            'batch_failures' => 0,
            'ai_errors' => 0,
            'imported' => 0,
            'persistence_failures' => 0,
        ];
        $this->duplicates->reset();
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     * @param  array<string, array<string, mixed>>  $sourceRecordsByRef
     * @param  array<string, mixed>  $taxonomy
     */
    private function processBatch(array $batch, array $sourceRecordsByRef, array $taxonomy): void
    {
        $this->stats['batches']++;

        try {
            $output = $this->aiClient->classify($batch, $taxonomy);
            $classifications = $this->validator->validate($batch, $taxonomy, $output);
        } catch (Throwable $exception) {
            $this->stats['batch_failures']++;
            Log::warning('Place CSV import batch skipped.', [
                'stage' => $this->failureStage($exception),
                'reason' => $this->failureReason($exception),
                'batch_size' => count($batch),
                'record_refs' => array_column($batch, 'record_ref'),
                'taxonomy' => $this->taxonomySummary($taxonomy),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        foreach ($batch as $aiRecord) {
            $recordRef = $aiRecord['record_ref'];
            $classification = $classifications[$recordRef];

            if ($classification['error']) {
                $this->stats['ai_errors']++;
                Log::warning('Place CSV import record rejected.', [
                    'record_ref' => $recordRef,
                    'reason' => $classification['error_reason'],
                ]);

                continue;
            }

            try {
                $place = $this->persistence->import(
                    $sourceRecordsByRef[$recordRef]['import_data'],
                    $classification,
                );

                if ($place === null) {
                    $this->stats['duplicates']++;

                    continue;
                }

                $this->stats['imported']++;
            } catch (Throwable $exception) {
                $this->stats['persistence_failures']++;
                Log::warning('Place CSV import record failed to persist.', [
                    'record_ref' => $recordRef,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $taxonomy
     * @return array{categories_count: int, districts_count: int, tags_count: int, empty_sections: array<int, string>}
     */
    private function taxonomySummary(array $taxonomy): array
    {
        $summary = [
            'categories_count' => count($taxonomy['categories'] ?? []),
            'districts_count' => count($taxonomy['districts'] ?? []),
            'tags_count' => count($taxonomy['tags'] ?? []),
            'empty_sections' => [],
        ];

        foreach (['categories', 'districts', 'tags'] as $section) {
            if ($summary[$section.'_count'] === 0) {
                $summary['empty_sections'][] = $section;
            }
        }

        return $summary;
    }

    private function failureStage(Throwable $exception): string
    {
        return str_starts_with($exception->getMessage(), 'ai_') ? 'ai_request_or_parse' : 'validation';
    }

    private function failureReason(Throwable $exception): string
    {
        return str_contains($exception->getMessage(), ':')
            ? (string) strstr($exception->getMessage(), ':', true)
            : 'unknown';
    }

    private function report(): void
    {
        Log::info('Place CSV import finished.', $this->stats);

        if ($this->command !== null) {
            $this->command->info('Place CSV import finished: '.json_encode($this->stats, JSON_UNESCAPED_UNICODE));
        }
    }
}
