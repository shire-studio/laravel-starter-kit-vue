<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

final readonly class UpdateTeamMember
{
    /**
     * @param  array{role: string}  $attributes
     */
    public function handle(Team $team, User $member, array $attributes): void
    {
        $team->memberships()
            ->where('user_id', $member->id)
            ->firstOrFail()
            ->update(['role' => TeamRole::from($attributes['role'])]);
    }
}
