<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cho phép User thường xin làm Sub-admin cho một place đã tồn tại:
     * - `place_request_id` trở thành nullable (đơn cũ luôn gắn place request mới).
     * - Thêm `place_id` nullable để trỏ thẳng tới place hiện hữu.
     * - Thêm `user_id` nullable: người xin, đã là user hệ thống (khác luồng tạo account mới).
     */
    public function up(): void
    {
        Schema::table('manager_applications', function (Blueprint $table): void {
            $table->foreignId('place_request_id')->nullable()->change();
            $table->foreignId('place_id')->nullable()->after('place_request_id')
                ->constrained('places')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->after('place_id')
                ->constrained('users')->restrictOnDelete();
            $table->index('place_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('manager_applications', function (Blueprint $table): void {
            $table->dropIndex(['manager_applications_place_id_index']);
            $table->dropIndex(['manager_applications_user_id_index']);
            $table->dropConstrainedForeignId('place_id');
            $table->dropConstrainedForeignId('user_id');
            $table->foreignId('place_request_id')->nullable(false)->change();
        });
    }
};
