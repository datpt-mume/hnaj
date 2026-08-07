<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enforce rating domain on places.rating.
 *
 * DECIMAL(2,1) alone permits values up to 9.9, but the discovery ranking
 * contract (docs/prd.md §5.1) defines 0.0–5.0 with one decimal. This adds a
 * CHECK constraint at the database boundary; the application layer must also
 * validate before writing (see Place model / future rating job).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE places ADD CONSTRAINT places_rating_check CHECK (rating >= 0.0 AND rating <= 5.0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE places DROP CONSTRAINT places_rating_check');
    }
};
