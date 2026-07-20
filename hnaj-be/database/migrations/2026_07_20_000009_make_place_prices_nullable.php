<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->unsignedInteger('price_min')->nullable()->change();
            $table->unsignedInteger('price_max')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->unsignedInteger('price_min')->nullable(false)->change();
            $table->unsignedInteger('price_max')->nullable(false)->change();
        });
    }
};
