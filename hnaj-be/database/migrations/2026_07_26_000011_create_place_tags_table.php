<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_tags', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('place_id')->constrained('places')->restrictOnDelete();
                    $table->foreignId('tag_id')->constrained('tags')->restrictOnDelete();
                    $table->timestamps();
                    $table->unique(['place_id', 'tag_id']);
                    $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_tags');
    }
};
