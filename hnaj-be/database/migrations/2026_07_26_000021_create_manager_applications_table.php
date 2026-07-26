<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_applications', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('place_request_id')->constrained('place_requests')->restrictOnDelete();
                    $table->string('email')->index();
                    $table->string('representative_name');
                    $table->text('proof_reference')->nullable();
                    $table->string('status')->default('pending')->index();
                    $table->foreignId('approved_user_id')->nullable()->constrained('users')->restrictOnDelete();
                    $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
                    $table->dateTime('reviewed_at')->nullable();
                    $table->text('review_reason')->nullable();
                    $table->timestamps();
                    $table->index('place_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_applications');
    }
};
