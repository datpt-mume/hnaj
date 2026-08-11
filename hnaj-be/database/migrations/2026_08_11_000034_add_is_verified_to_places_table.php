<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->boolean('is_verified')->default(false)->after('status');
            $table->index('is_verified');
            $table->index(['is_verified', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->dropIndex(['is_verified']);
            $table->dropIndex(['is_verified', 'id']);
            $table->dropColumn('is_verified');
        });
    }
};
