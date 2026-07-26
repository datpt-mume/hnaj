<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_managers', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('place_id')->constrained('places')->restrictOnDelete();
                    $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                    $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
                    $table->dateTime('assigned_at');
                    $table->dateTime('revoked_at')->nullable();
                    $table->timestamps();
                    $table->unique(['place_id', 'user_id']);
                    $table->index(['user_id', 'place_id']);
                    $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_managers');
    }
};
