<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                    $table->foreignId('place_id')->constrained('places')->restrictOnDelete();
                    $table->timestamps();
                    $table->unique(['user_id', 'place_id']);
                    $table->index('user_id');
                    $table->index('place_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
