<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class LeaveTeam
{
    public function __construct(private SwitchTeam $switchTeam) {}

    public function handle(Team $team, User $user): void
    {
        DB::transaction(function () use ($team, $user): void {
            $team->memberships()->where('user_id', $user->id)->delete();

            $personal = $user->personalTeam();

            if ($user->isCurrentTeam($team) && $personal instanceof Team) {
                $this->switchTeam->handle($user, $personal);
            }
        });
    }
}
