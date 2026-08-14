<?php

namespace App\Actions\Admin\ManagerApplication;

use App\Enums\ManagerApplicationStatus;
use App\Enums\RoleName;
use App\Models\ManagerApplication;
use App\Models\PlaceManager;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Duyệt/từ chối đơn xin làm Sub-admin cho place hiện hữu.
 * Khi duyệt: gán thêm role sub_admin cho user (giữ role user), tạo assignment.
 */
class ReviewManagerApplication
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
    ) {}

    public function approve(ManagerApplication $application, User $admin): ManagerApplication
    {
        return DB::transaction(function () use ($application, $admin): ManagerApplication {
            $this->assertPending($application);

            $user = $application->applicant;

            if ($user === null) {
                throw new \DomainException('Đơn này không gắn với tài khoản người dùng.');
            }

            $this->users->assignRole($user, $this->roles->findByNameOrFail(RoleName::SubAdmin), $admin->id);

            $placeId = $application->place_id;

            if ($placeId !== null) {
                PlaceManager::query()->updateOrCreate(
                    ['place_id' => $placeId, 'user_id' => $user->id],
                    [
                        'assigned_by' => $admin->id,
                        'assigned_at' => Carbon::now(),
                        'revoked_at' => null,
                    ],
                );
            }

            $application->update([
                'status' => ManagerApplicationStatus::Approved,
                'approved_user_id' => $user->id,
                'reviewed_by' => $admin->id,
                'reviewed_at' => Carbon::now(),
                'review_reason' => null,
            ]);

            return $application->fresh(['place', 'applicant.roles']);
        });
    }

    public function reject(ManagerApplication $application, User $admin, string $reason): ManagerApplication
    {
        return DB::transaction(function () use ($application, $admin, $reason): ManagerApplication {
            $this->assertPending($application);

            $application->update([
                'status' => ManagerApplicationStatus::Rejected,
                'reviewed_by' => $admin->id,
                'reviewed_at' => Carbon::now(),
                'review_reason' => $reason,
            ]);

            return $application->fresh(['place', 'applicant.roles']);
        });
    }

    private function assertPending(ManagerApplication $application): void
    {
        if ($application->status !== ManagerApplicationStatus::Pending) {
            throw new \DomainException('Đơn đã được xử lý trước đó.');
        }
    }
}
