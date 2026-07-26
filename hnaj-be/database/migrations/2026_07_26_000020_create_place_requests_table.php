<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_requests', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
                    $table->foreignId('place_id')->nullable()->constrained('places')->restrictOnDelete();
                    $table->string('name_input');
                    $table->text('google_maps_url_input');
                    $table->text('address_text_input');
                    $table->foreignId('category_id_input')->constrained('categories')->restrictOnDelete();
                    $table->string('source_image_path')->nullable();
                    $table->json('normalized_data')->nullable();
                    $table->string('status')->default('pending')->index();
                    $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
                    $table->dateTime('reviewed_at')->nullable();
                    $table->text('review_reason')->nullable();
                    $table->timestamps();
                    $table->index('submitted_by');
                    $table->index('place_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_requests');
    }
};
