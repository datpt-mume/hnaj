<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('price_min');
            $table->unsignedInteger('price_max');
            $table->json('tags')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->string('address');
            $table->string('cover_image')->nullable();
            $table->string('status')->default('draft')->index();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'latitude', 'longitude']);
            $table->index(['status', 'price_max']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
