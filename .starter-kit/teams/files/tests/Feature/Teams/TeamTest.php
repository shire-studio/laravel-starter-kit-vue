<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

function teamFor(User $user, TeamRole $role, array $attributes = []): Team
{
    $team = Team::factory()->create($attributes);
    $team->members()->attach($user, ['role' => $role->value]);

    return $team;
}

it("lists the user's teams", function (): void {
    $user = User::factory()->create(['name' => 'Test User']);
    $personal = $user->personalTeam();
    $team = teamFor($user, TeamRole::Member, ['name' => 'Acme']);
    Team::factory()->create();

    $this->actingAs($user)
        ->get(route('teams.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('teams/Index')
            ->has('teams', 2)
            ->where('teams.0.id', $team->id)
            ->where('teams.0.name', 'Acme')
            ->where('teams.0.role', 'member')
            ->where('teams.0.is_current', false)
            ->where('teams.0.can_leave', true)
            ->where('teams.0.can_update', false)
            ->where('teams.1.id', $personal->id)
            ->where('teams.1.is_personal', true)
            ->where('teams.1.role', 'owner')
            ->where('teams.1.is_current', true)
            ->where('teams.1.can_leave', false)
            ->where('teams.1.can_update', true));
});

it('creates a team', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('teams.store'), ['name' => 'Acme'])
        ->assertSessionHasNoErrors();

    $team = Team::query()->where('name', 'Acme')->firstOrFail();

    expect($user->fresh()->current_team_id)->toBe($team->id)
        ->and($user->ownsTeam($team))->toBeTrue()
        ->and($team->is_personal)->toBeFalse();
});

it('redirects to the new team after creating it', function (): void {
    $this->actingAs(User::factory()->create())
        ->fromRoute('teams.index')
        ->post(route('teams.store'), ['name' => 'Acme'])
        ->assertRedirectToRoute('teams.edit', ['team' => Team::query()->where('name', 'Acme')->firstOrFail()->slug])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Team created.']);
});

it('rejects a reserved team name', function (): void {
    $this->actingAs(User::factory()->create())
        ->fromRoute('teams.index')
        ->post(route('teams.store'), ['name' => 'teams'])
        ->assertRedirectToRoute('teams.index')
        ->assertSessionHasErrors('name');
});

it('requires a team name', function (): void {
    $this->actingAs(User::factory()->create())
        ->fromRoute('teams.index')
        ->post(route('teams.store'), ['name' => ''])
        ->assertRedirectToRoute('teams.index')
        ->assertSessionHasErrors('name');
});

it('shows a team to members', function (): void {
    $owner = User::factory()->create(['name' => 'Olivia Owner']);
    $team = teamFor($owner, TeamRole::Owner, ['name' => 'Acme']);
    $member = User::factory()->create(['name' => 'Mike Member']);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $invitation = TeamInvitation::factory()->for($team)->create([
        'email' => 'new@example.com',
        'role' => TeamRole::Admin,
        'invited_by' => $owner->id,
    ]);
    TeamInvitation::factory()->for($team)->expired()->create([
        'email' => 'old@example.com',
        'role' => TeamRole::Admin,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->get(route('teams.edit', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('teams/Edit')
            ->where('team.id', $team->id)
            ->where('team.name', 'Acme')
            ->where('team.role', 'owner')
            ->where('team.can_leave', false)
            ->where('team.can_update', true)
            ->has('members', 2)
            ->where('members.0.id', $member->id)
            ->where('members.0.role', 'member')
            ->where('members.0.can_update', true)
            ->where('members.0.can_remove', true)
            ->where('members.1.id', $owner->id)
            ->where('members.1.role', 'owner')
            ->where('members.1.can_update', false)
            ->where('members.1.can_remove', false)
            ->has('invitations', 1)
            ->where('invitations.0.code', $invitation->code)
            ->where('invitations.0.email', 'new@example.com')
            ->where('invitations.0.role', 'admin')
            ->where('permissions.can_update_team', true)
            ->where('permissions.can_delete_team', true)
            ->where('permissions.can_add_member', true)
            ->where('permissions.can_update_member', true)
            ->where('permissions.can_remove_member', true)
            ->where('permissions.can_create_invitation', true)
            ->where('permissions.can_cancel_invitation', true)
            ->where('availableRoles', [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'member', 'label' => 'Member'],
            ]));
});

it('hides invitations from members without the invitation permission', function (): void {
    $owner = User::factory()->create();
    $team = teamFor($owner, TeamRole::Owner);
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    TeamInvitation::factory()->for($team)->create([
        'email' => 'new@example.com',
        'role' => TeamRole::Admin,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($member)
        ->get(route('teams.edit', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('team.can_leave', true)
            ->where('team.can_update', false)
            ->where('members.0.can_update', false)
            ->where('members.0.can_remove', false)
            ->where('members.1.can_update', false)
            ->where('members.1.can_remove', false)
            ->where('invitations', [])
            ->where('permissions', [
                'can_update_team' => false,
                'can_delete_team' => false,
                'can_add_member' => false,
                'can_update_member' => false,
                'can_remove_member' => false,
                'can_create_invitation' => false,
                'can_cancel_invitation' => false,
            ]));
});

it('hides a team from non members', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('teams.edit', Team::factory()->create()))
        ->assertNotFound();
});

it('lets admins rename a team', function (): void {
    $user = User::factory()->create();
    $team = teamFor($user, TeamRole::Admin, ['name' => 'Acme']);

    $this->actingAs($user)
        ->fromRoute('teams.edit', $team)
        ->patch(route('teams.update', $team), ['name' => 'Globex'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Team updated.']);

    expect($team->fresh()->name)->toBe('Globex');
});

it('redirects to the renamed team', function (): void {
    $user = User::factory()->create();
    $team = teamFor($user, TeamRole::Owner, ['name' => 'Acme']);

    $this->actingAs($user)
        ->fromRoute('teams.edit', $team)
        ->patch(route('teams.update', $team), ['name' => 'Globex'])
        ->assertRedirectToRoute('teams.edit', ['team' => $team->fresh()->slug]);

    expect($team->fresh()->slug)->not->toBe($team->slug);
});

it('requires a team name when renaming', function (): void {
    $user = User::factory()->create();
    $team = teamFor($user, TeamRole::Owner, ['name' => 'Acme']);

    $this->actingAs($user)
        ->fromRoute('teams.edit', $team)
        ->patch(route('teams.update', $team), ['name' => ''])
        ->assertRedirectToRoute('teams.edit', $team)
        ->assertSessionHasErrors('name');

    expect($team->fresh()->name)->toBe('Acme');
});

it('forbids members renaming a team', function (): void {
    $user = User::factory()->create();
    $team = teamFor($user, TeamRole::Member, ['name' => 'Acme']);

    $this->actingAs($user)
        ->patch(route('teams.update', $team), ['name' => 'Globex'])
        ->assertNotFound();

    expect($team->fresh()->name)->toBe('Acme');
});

it('deletes a team when the name matches', function (): void {
    $user = User::factory()->create();
    $team = teamFor($user, TeamRole::Owner, ['name' => 'Acme']);

    $this->actingAs($user)
        ->fromRoute('teams.edit', $team)
        ->delete(route('teams.destroy', $team), ['name' => 'Acme'])
        ->assertRedirectToRoute('teams.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Team deleted.']);

    expect(Team::query()->find($team->id))->toBeNull();
});

it('rejects deleting with a different name', function (): void {
    $user = User::factory()->create();
    $team = teamFor($user, TeamRole::Owner, ['name' => 'Acme']);

    $this->actingAs($user)
        ->fromRoute('teams.edit', $team)
        ->delete(route('teams.destroy', $team), ['name' => 'Other'])
        ->assertRedirectToRoute('teams.edit', $team)
        ->assertSessionHasErrors(['name' => 'The team name does not match.']);

    expect(Team::query()->find($team->id))->not->toBeNull();
});

it('forbids deleting a personal team', function (): void {
    $user = User::factory()->create();
    $personal = $user->personalTeam();

    $this->actingAs($user)
        ->delete(route('teams.destroy', $personal), ['name' => $personal->name])
        ->assertNotFound();
});

it('lets members leave a team', function (): void {
    $user = User::factory()->create();
    $team = teamFor($user, TeamRole::Member, ['name' => 'Acme']);

    $this->actingAs($user)
        ->fromRoute('teams.edit', $team)
        ->delete(route('teams.leave', $team))
        ->assertRedirectToRoute('teams.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'You left the team "Acme"']);

    expect($user->belongsToTeam($team))->toBeFalse();
});

it('forbids owners leaving their team', function (): void {
    $user = User::factory()->create();
    $team = teamFor($user, TeamRole::Owner);

    $this->actingAs($user)
        ->delete(route('teams.leave', $team))
        ->assertNotFound();

    expect($user->belongsToTeam($team))->toBeTrue();
});

it('redirects guests to login', function (): void {
    $this->get(route('teams.index'))->assertRedirectToRoute('login');
});

it('does not offer inviting on a personal team', function (): void {
    $user = User::factory()->create();
    $team = $user->personalTeam();
    TeamInvitation::factory()->for($team)->create([
        'email' => 'old@example.com',
        'role' => TeamRole::Member,
        'invited_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('teams.edit', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('invitations', 1)
            ->where('permissions.can_add_member', false)
            ->where('permissions.can_create_invitation', false)
            ->where('permissions.can_cancel_invitation', true));
});
