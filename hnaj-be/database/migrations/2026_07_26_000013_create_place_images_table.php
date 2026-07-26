<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_images', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('place_id')->constrained('places')->restrictOnDelete();
                    $table->foreignId('uploaded_by')->nullable()->constrained('users')->restrictOnDelete();
                    $table->text('image_url');
                    $table->string('alt_text')->nullable();
                    $table->boolean('is_visible')->default(true)->index();
                    $table->timestamps();
                    $table->softDeletes();
                    $table->index('place_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_images');
    }
};
