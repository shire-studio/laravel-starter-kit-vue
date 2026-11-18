<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\CancelTeamInvitation;
use App\Actions\CreateTeamInvitation;
use App\Http\Requests\CreateTeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class TeamInvitationController
{
    public function store(Team $team, CreateTeamInvitationRequest $request, #[CurrentUser] User $user, CreateTeamInvitation $createTeamInvitation): RedirectResponse
    {
        /** @var array{email: string, role: string} $attributes */
        $attributes = $request->validated();

        $createTeamInvitation->handle($team, $user, $attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    public function destroy(Team $team, TeamInvitation $invitation, CancelTeamInvitation $cancelTeamInvitation): RedirectResponse
    {
        $cancelTeamInvitation->handle($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
