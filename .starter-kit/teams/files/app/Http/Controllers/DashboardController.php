<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PendingTeamInvitationData;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __invoke(Team $current_team, #[CurrentUser] User $user): Response
    {
        $invitations = TeamInvitation::query();
        $invitations->scopes(['pendingFor' => [$user]]);

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $invitations->get()
                ->map(PendingTeamInvitationData::fromInvitation(...))
                ->values()
                ->all(),
        ]);
    }
}
