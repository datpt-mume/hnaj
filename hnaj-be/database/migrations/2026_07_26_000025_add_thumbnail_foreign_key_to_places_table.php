<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->foreign('thumbnail_image_id')
                ->references('id')
                ->on('place_images')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->dropForeign(['thumbnail_image_id']);
        });
    }
};
