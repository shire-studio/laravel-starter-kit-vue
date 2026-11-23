<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteTeam
{
    public function __construct(private SwitchTeam $switchTeam) {}

    public function handle(Team $team, User $user): void
    {
        DB::transaction(function () use ($team, $user): void {
            $affected = User::query()
                ->where('current_team_id', $team->id)
                ->where('id', '!=', $user->id)
                ->pluck('id');

            $team->invitations()->delete();
            $team->memberships()->delete();
            $team->delete();

            User::query()->whereKey($affected)->each(function (User $member): void {
                $replacement = $member->currentTeamOrPersonal();

                if ($replacement instanceof Team) {
                    $this->switchTeam->handle($member, $replacement);
                }
            });

            $personal = $user->personalTeam();

            if ($user->isCurrentTeam($team) && $personal instanceof Team) {
                $this->switchTeam->handle($user, $personal);
            }
        });
    }
}
