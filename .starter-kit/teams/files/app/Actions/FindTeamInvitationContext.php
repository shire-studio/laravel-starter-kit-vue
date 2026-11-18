<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TeamInvitation;

final readonly class FindTeamInvitationContext
{
    public function handle(?string $code): ?TeamInvitation
    {
        if ($code === null) {
            return null;
        }

        $invitations = TeamInvitation::query();
        $invitations->scopes(['pending', 'joinable']);

        $invitation = $invitations->where('code', $code)->with('team')->first();

        return $invitation instanceof TeamInvitation ? $invitation : null;
    }
}
