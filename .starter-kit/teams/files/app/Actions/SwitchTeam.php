<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;

final readonly class SwitchTeam
{
    public function handle(User $user, Team $team): void
    {
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user->setRelation('currentTeam', $team);
    }
}
