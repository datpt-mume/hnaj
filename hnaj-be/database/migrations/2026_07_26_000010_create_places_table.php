<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table): void {
                    $table->id();
                    $table->string('name');
                    $table->text('address_text');
                    $table->string('google_place_id')->nullable()->unique();
                    $table->string('phone')->nullable();
                    $table->text('website_url')->nullable();
                    $table->text('google_maps_url');
                    $table->foreignId('district_id')->constrained('districts')->restrictOnDelete();
                    $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
                    $table->decimal('latitude', 10, 7);
                    $table->decimal('longitude', 10, 7);
                    $table->unsignedBigInteger('min_price')->nullable();
                    $table->unsignedBigInteger('max_price')->nullable();
                    $table->text('description')->nullable();
                    $table->unsignedBigInteger('thumbnail_image_id')->nullable();
                    $table->string('status')->default('active')->index();
                    $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
                    $table->timestamps();
                    $table->softDeletes();
                    $table->index('district_id');
                    $table->index('category_id');
                    $table->index('min_price');
                    $table->index('max_price');
                    $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
