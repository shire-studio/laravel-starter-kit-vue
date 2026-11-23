<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class AcceptTeamInvitation
{
    public function __construct(private SwitchTeam $switchTeam) {}

    public function handle(TeamInvitation $invitation, User $user): Team
    {
        return DB::transaction(function () use ($invitation, $user): Team {
            $team = $invitation->team;

            $team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);

            $this->switchTeam->handle($user, $team);

            return $team;
        });
    }
}
