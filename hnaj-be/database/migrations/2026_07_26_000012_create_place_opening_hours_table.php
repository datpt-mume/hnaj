<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_opening_hours', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('place_id')->constrained('places')->restrictOnDelete();
                    $table->unsignedTinyInteger('day_of_week');
                    $table->string('schedule_type');
                    $table->time('opens_at')->nullable();
                    $table->time('closes_at')->nullable();
                    $table->boolean('crosses_midnight')->default(false);
                    $table->timestamps();
                    $table->index(['place_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_opening_hours');
    }
};
