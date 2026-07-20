<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('place_import_batches', function (Blueprint $table): void {
            $table->unsignedInteger('processed_rows')->default(0)->after('invalid_rows');
            $table->unsignedInteger('imported_rows')->default(0)->after('processed_rows');
            $table->unsignedInteger('skipped_rows')->default(0)->after('imported_rows');
            $table->unsignedInteger('failed_rows')->default(0)->after('skipped_rows');
            $table->unsignedTinyInteger('progress_percent')->default(0)->after('failed_rows');
            $table->timestamp('started_at')->nullable()->after('confirmed_at');
            $table->timestamp('paused_at')->nullable()->after('started_at');
            $table->timestamp('progress_updated_at')->nullable()->after('paused_at');
        });

        Schema::table('place_import_rows', function (Blueprint $table): void {
            $table->timestamp('processed_at')->nullable()->after('errors');
            $table->unsignedTinyInteger('attempts')->default(0)->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('place_import_rows', function (Blueprint $table): void {
            $table->dropColumn(['processed_at', 'attempts']);
        });

        Schema::table('place_import_batches', function (Blueprint $table): void {
            $table->dropColumn([
                'processed_rows', 'imported_rows', 'skipped_rows', 'failed_rows',
                'progress_percent', 'started_at', 'paused_at', 'progress_updated_at',
            ]);
        });
    }
};