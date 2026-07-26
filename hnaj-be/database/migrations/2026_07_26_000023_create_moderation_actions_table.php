<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_actions', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
                    $table->string('target_type');
                    $table->unsignedBigInteger('target_id');
                    $table->string('action');
                    $table->text('reason')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamp('created_at')->useCurrent();
                    $table->index(['target_type', 'target_id']);
                    $table->index('performed_by');
                    $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_actions');
    }
};
