<?php

namespace App\Actions\Admin\Place;

use App\Actions\Auth\IssueAccountSetupToken;
use App\Models\PlaceManager;
use Illuminate\Support\Carbon;

/**
 * Gửi lại email activation cho Sub-admin chưa kích hoạt.
 */
class ResendPlaceManagerSetup
{
    public function __construct(
        private readonly IssueAccountSetupToken $issueSetupToken,
    ) {}

    public function handle(PlaceManager $manager): void
    {
        $this->issueSetupToken->handle($manager->user);
    }
}
