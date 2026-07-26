<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_tags', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
                    $table->foreignId('tag_id')->constrained('tags')->restrictOnDelete();
                    $table->timestamps();
                    $table->unique(['category_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_tags');
    }
};
