<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 50)->nullable()->after('name');
            $table->string('google_id', 64)->nullable()->after('email_verified_at');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->unique('google_id');
        });

        DB::table('users')
            ->select(['id', 'email'])
            ->orderBy('id')
            ->each(function (object $user): void {
                $base = Str::lower(Str::ascii(Str::before((string) $user->email, '@')));
                $base = preg_replace('/[^a-z0-9._]/', '', $base) ?: 'user';
                $base = trim($base, '._');

                if (strlen($base) < 3) {
                    $base = 'user'.$user->id;
                }

                $base = substr($base, 0, 50);
                $candidate = $base;
                $suffix = 1;

                while (DB::table('users')->where('username', $candidate)->exists()) {
                    $tail = '.'.$suffix;
                    $candidate = substr($base, 0, 50 - strlen($tail)).$tail;
                    $suffix++;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'username' => $candidate,
                ]);
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 50)->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropUnique(['username']);
            $table->dropColumn(['avatar_url', 'google_id', 'username']);
        });
    }
};
