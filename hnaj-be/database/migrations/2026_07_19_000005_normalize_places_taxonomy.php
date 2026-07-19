<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->foreignUlid('category_id')->nullable()->after('slug')->constrained('categories')->nullOnDelete();
            $table->text('description')->nullable()->after('address');
            $table->string('website')->nullable()->after('description');
            $table->string('phone')->nullable()->after('website');
            $table->json('opening_hours')->nullable()->after('phone');
            $table->json('amenities')->nullable()->after('opening_hours');
            $table->json('gallery')->nullable()->after('cover_image');
            $table->string('source_category')->nullable()->after('gallery');
            $table->index(['category_id', 'status']);
        });

        Schema::create('place_external_ids', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('place_id')->constrained('places')->cascadeOnDelete();
            $table->string('provider');
            $table->string('external_id');
            $table->string('fingerprint')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'external_id']);
            $table->unique(['place_id', 'provider']);
            $table->index('fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_external_ids');
        Schema::table('places', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id', 'status']);
            $table->dropColumn([
                'category_id', 'description', 'website', 'phone', 'opening_hours',
                'amenities', 'gallery', 'source_category',
            ]);
        });
    }
};
