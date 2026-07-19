<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_areas', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type');
            $table->foreignUlid('parent_id')->nullable()->constrained('administrative_areas')->nullOnDelete();
            $table->string('city')->default('Hà Nội')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['name', 'type', 'parent_id']);
            $table->index(['type', 'parent_id']);
        });

        Schema::table('places', function (Blueprint $table): void {
            $table->foreignUlid('district_id')->nullable()->after('category_id')->constrained('administrative_areas')->nullOnDelete();
            $table->foreignUlid('ward_id')->nullable()->after('district_id')->constrained('administrative_areas')->nullOnDelete();
            $table->index(['district_id', 'ward_id']);
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->dropForeign(['ward_id']);
            $table->dropForeign(['district_id']);
            $table->dropIndex(['district_id', 'ward_id']);
            $table->dropColumn(['district_id', 'ward_id']);
        });
        Schema::dropIfExists('administrative_areas');
    }
};
