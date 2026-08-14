<?php

namespace App\Actions\ManagerApplication;

use App\Models\ManagerApplication;
use App\Models\Place;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * User thường xin làm Sub-admin cho một place đã tồn tại.
 */
class SubmitManagerApplication
{
    /**
     * @param  array<string, string>  $data
     */
    public function handle(User $applicant, Place $place, array $data): ManagerApplication
    {
        return DB::transaction(function () use ($applicant, $place, $data): ManagerApplication {
            return ManagerApplication::query()->create([
                'place_id' => $place->id,
                'user_id' => $applicant->id,
                'email' => $data['email'] ?? $applicant->email,
                'representative_name' => $data['representative_name'] ?? $applicant->name,
                'proof_reference' => $data['proof_reference'] ?? null,
                'status' => \App\Enums\ManagerApplicationStatus::Pending,
            ]);
        });
    }
}
