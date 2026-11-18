<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\CreateTeam;
use App\Actions\DeleteTeam;
use App\Actions\UpdateTeam;
use App\Data\TeamData;
use App\Data\TeamInvitationData;
use App\Data\TeamMemberData;
use App\Data\TeamPermissionsData;
use App\Data\TeamRoleData;
use App\Enums\TeamRole;
use App\Http\Requests\DeleteTeamRequest;
use App\Http\Requests\SaveTeamRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TeamController
{
    public function index(#[CurrentUser] User $user): Response
    {
        return Inertia::render('teams/Index', [
            'teams' => $user->teams()->orderByRaw('LOWER(teams.name)')->get()
                ->map(fn (Team $team): TeamData => TeamData::fromTeam($team, $user))
                ->all(),
        ]);
    }

    public function store(SaveTeamRequest $request, #[CurrentUser] User $user, CreateTeam $createTeam): RedirectResponse
    {
        /** @var array{name: string} $attributes */
        $attributes = $request->validated();

        $team = $createTeam->handle($user, $attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team created.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    public function edit(Team $team, #[CurrentUser] User $user): Response
    {
        $team = $user->teamWithMembership($team);
        $permissions = TeamPermissionsData::fromTeam($team, $user);

        $invitations = $team->invitations()->oldest();
        $invitations->scopes('pending');

        return Inertia::render('teams/Edit', [
            'team' => TeamData::fromTeam($team, $user),
            'members' => $team->members()->orderBy('name')->get()
                ->map(fn (User $member): TeamMemberData => TeamMemberData::fromMember($member, $team, $user))
                ->all(),
            'invitations' => $permissions->can_cancel_invitation
                ? $invitations->get()
                    ->map(TeamInvitationData::fromInvitation(...))
                    ->all()
                : [],
            'permissions' => $permissions,
            'availableRoles' => array_map(TeamRoleData::fromRole(...), TeamRole::assignable()),
        ]);
    }

    public function update(Team $team, SaveTeamRequest $request, UpdateTeam $updateTeam): RedirectResponse
    {
        /** @var array{name: string} $attributes */
        $attributes = $request->validated();

        $team = $updateTeam->handle($team, $attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team updated.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    public function destroy(Team $team, DeleteTeamRequest $request, #[CurrentUser] User $user, DeleteTeam $deleteTeam): RedirectResponse
    {
        $deleteTeam->handle($team, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team deleted.')]);

        return to_route('teams.index');
    }
}
