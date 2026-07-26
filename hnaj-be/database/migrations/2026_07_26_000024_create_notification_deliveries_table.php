<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
                    $table->string('recipient_email');
                    $table->string('notifiable_type');
                    $table->unsignedBigInteger('notifiable_id');
                    $table->string('notification_type');
                    $table->string('status')->default('pending')->index();
                    $table->dateTime('sent_at')->nullable();
                    $table->text('failure_reason')->nullable();
                    $table->timestamps();
                    $table->index('user_id');
                    $table->index(['notifiable_type', 'notifiable_id']);
                    $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
