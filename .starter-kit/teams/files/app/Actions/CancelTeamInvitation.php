<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TeamInvitation;

final readonly class CancelTeamInvitation
{
    public function handle(TeamInvitation $invitation): void
    {
        $invitation->delete();
    }
}
