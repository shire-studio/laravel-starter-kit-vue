<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class RemoveTeamMember
{
    public function __construct(private SwitchTeam $switchTeam) {}

    public function handle(Team $team, User $member): void
    {
        DB::transaction(function () use ($team, $member): void {
            $team->memberships()->where('user_id', $member->id)->delete();

            $personal = $member->personalTeam();

            if ($member->isCurrentTeam($team) && $personal instanceof Team) {
                $this->switchTeam->handle($member, $personal);
            }
        });
    }
}
