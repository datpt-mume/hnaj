<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add aggregate rating column for places.
 *
 * `rating` is a denormalized value computed from User HNAJ reviews by a
 * scheduled job (out of scope, not yet implemented) — NOT imported from
 * Google, so the "no Google rating import" decision in docs/prd.md stands.
 *
 * Places without any review default to 5.0 so they are not penalized in a
 * discovery round for missing data, same principle as open_now treating
 * unknown hours as open.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->decimal('rating', 2, 1)->default(5.0)->after('max_price');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->dropIndex(['rating']);
            $table->dropColumn('rating');
        });
    }
};
