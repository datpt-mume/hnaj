<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('place_opening_hours', function (Blueprint $table): void {
            $table->dropColumn('crosses_midnight');
        });
    }

    public function down(): void
    {
        Schema::table('place_opening_hours', function (Blueprint $table): void {
            $table->boolean('crosses_midnight')->default(false)->after('closes_at');
        });
    }
};
