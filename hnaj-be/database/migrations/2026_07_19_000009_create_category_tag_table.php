<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_tag', function (Blueprint $table): void {
            $table->foreignUlid('category_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_tag');
    }
};