<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_events', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                    $table->foreignId('place_id')->constrained('places')->restrictOnDelete();
                    $table->date('visit_date');
                    $table->dateTime('visited_at');
                    $table->string('source')->nullable();
                    $table->timestamps();
                    $table->unique(['user_id', 'place_id', 'visit_date']);
                    $table->index(['place_id', 'visit_date']);
                    $table->index(['user_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_events');
    }
};
