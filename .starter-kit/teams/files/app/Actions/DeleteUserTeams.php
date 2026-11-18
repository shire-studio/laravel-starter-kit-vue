<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Membership;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUserTeams
{
    public function __construct(private DeleteTeam $deleteTeam) {}

    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->ownedTeams()->get()->each(
                fn (Team $team) => $this->deleteTeam->handle($team, $user),
            );

            Membership::query()->where('user_id', $user->id)->delete();
            TeamInvitation::query()->where('invited_by', $user->id)->delete();

            $user->forceFill(['current_team_id' => null])->save();
        });
    }
}
