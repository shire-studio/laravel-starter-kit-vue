<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\RemoveTeamMember;
use App\Actions\UpdateTeamMember;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class TeamMemberController
{
    public function update(Team $team, User $user, UpdateTeamMemberRequest $request, UpdateTeamMember $updateTeamMember): RedirectResponse
    {
        /** @var array{role: string} $attributes */
        $attributes = $request->validated();

        $updateTeamMember->handle($team, $user, $attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    public function destroy(Team $team, User $user, RemoveTeamMember $removeTeamMember): RedirectResponse
    {
        $removeTeamMember->handle($team, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
