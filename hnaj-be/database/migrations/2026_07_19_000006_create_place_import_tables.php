<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_import_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('filename');
            $table->string('status')->default('uploaded')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('place_import_rows', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('batch_id')->constrained('place_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('external_id')->nullable();
            $table->string('fingerprint')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->json('normalized_data')->nullable();
            $table->json('classification')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();
            $table->unique(['batch_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_import_rows');
        Schema::dropIfExists('place_import_batches');
    }
};
