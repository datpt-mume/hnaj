<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_setup_tokens', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                    $table->string('token_hash')->unique();
                    $table->dateTime('expires_at')->index();
                    $table->dateTime('used_at')->nullable();
                    $table->timestamp('created_at')->useCurrent();
                    $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_setup_tokens');
    }
};
