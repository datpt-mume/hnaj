<?php

namespace App\Actions\Admin\Place;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Place;
use App\Models\PlaceManager;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tạo user Sub-admin thủ công cho một place: user mới + role sub_admin +
 * assignment trong place_managers + token activation (email đặt password).
 * Account chưa thể đăng nhập cho tới khi kích hoạt vì email chưa verified.
 */
class CreatePlaceManager
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly \App\Actions\Auth\IssueAccountSetupToken $issueSetupToken,
    ) {}

    /**
     * @param  array<string, string>  $data
     */
    public function handle(Place $place, User $admin, array $data): User
    {
        return DB::transaction(function () use ($place, $admin, $data): User {
            $user = $this->users->create([
                'name' => trim($data['full_name'] ?? ''),
                'username' => mb_strtolower(trim($data['username'])),
                'email' => mb_strtolower(trim($data['email'])),
                'password' => $data['password'],
                'status' => UserStatus::Active,
            ]);

            $this->users->assignRole($user, $this->roles->findByNameOrFail(RoleName::SubAdmin), $admin->id);

            PlaceManager::query()->create([
                'place_id' => $place->id,
                'user_id' => $user->id,
                'assigned_by' => $admin->id,
                'assigned_at' => Carbon::now(),
            ]);

            $this->issueSetupToken->handle($user);

            return $user->load('roles');
        });
    }
}
