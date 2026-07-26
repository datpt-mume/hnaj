<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('place_id')->constrained('places')->restrictOnDelete();
                    $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                    $table->foreignId('parent_id')->nullable()->constrained('comments')->restrictOnDelete();
                    $table->text('body');
                    $table->string('status')->default('published')->index();
                    $table->timestamps();
                    $table->softDeletes();
                    $table->index(['place_id', 'status']);
                    $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
