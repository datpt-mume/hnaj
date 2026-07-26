<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anonymous_visit_events', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('place_id')->constrained('places')->restrictOnDelete();
                    $table->string('anonymous_key_hash', 128);
                    $table->date('visit_date');
                    $table->dateTime('visited_at');
                    $table->string('source')->nullable();
                    $table->timestamps();
                    $table->unique(['place_id', 'anonymous_key_hash', 'visit_date'], 'anon_visit_unique');
                    $table->index(['place_id', 'visit_date'], 'anon_visit_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymous_visit_events');
    }
};
