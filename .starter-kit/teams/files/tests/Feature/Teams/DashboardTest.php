<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

it('redirects the dashboard to the current team', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user)
        ->fromRoute('home')
        ->get(route('dashboard'))
        ->assertRedirectToRoute('team.dashboard', ['current_team' => $team->slug]);
});

it('falls back to the personal team when no current team is set', function (): void {
    $user = User::factory()->create();
    $user->update(['current_team_id' => null]);

    $this->actingAs($user->refresh())
        ->fromRoute('home')
        ->get(route('dashboard'))
        ->assertRedirectToRoute('team.dashboard', ['current_team' => $user->personalTeam()?->slug]);
});

it('shows the team dashboard to members', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('team.dashboard', ['current_team' => $user->personalTeam()?->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('currentTeam.slug', $user->personalTeam()?->slug)
            ->where('currentTeam.role', 'owner')
            ->has('teams', 1)
            ->where('pendingInvitations', []));
});

it("hides another team's dashboard", function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->get(route('team.dashboard', ['current_team' => $other->personalTeam()?->slug]))
        ->assertNotFound();
});

it('switches the current team from the url', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    $this->actingAs($user)
        ->get(route('team.dashboard', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentTeam.slug', $team->slug)
            ->where('currentTeam.is_current', true));

    expect($user->refresh()->current_team_id)->toBe($team->id);
});

it("lists pending invitations for the user's email", function (): void {
    $user = User::factory()->create(['email' => 'Invitee@Example.com']);
    $team = Team::factory()->create(['name' => 'Acme']);
    $inviter = User::factory()->create(['name' => 'Ivy Inviter']);

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $inviter->id,
        'email' => 'invitee@example.com',
        'code' => 'pending-code',
    ]);
    TeamInvitation::factory()->expired()->create(['email' => 'invitee@example.com']);
    TeamInvitation::factory()->accepted()->create(['email' => 'invitee@example.com']);
    TeamInvitation::factory()->create(['team_id' => Team::factory()->personal()->create()->id, 'email' => 'invitee@example.com']);
    TeamInvitation::factory()->create(['email' => 'someone-else@example.com']);

    $this->actingAs($user)
        ->get(route('team.dashboard', ['current_team' => $user->personalTeam()?->slug]))
        ->assertInertia(fn ($page) => $page
            ->has('pendingInvitations', 1)
            ->where('pendingInvitations.0.code', 'pending-code')
            ->where('pendingInvitations.0.inviter_name', 'Ivy Inviter')
            ->where('pendingInvitations.0.team_name', 'Acme')
            ->where('pendingInvitations.0.team_slug', $team->slug));
});

it('requires a verified email', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->fromRoute('home')
        ->get(route('team.dashboard', ['current_team' => $user->personalTeam()?->slug]))
        ->assertRedirectToRoute('verification.notice');

    $this->actingAs($user)
        ->fromRoute('home')
        ->get(route('dashboard'))
        ->assertRedirectToRoute('verification.notice');
});

it('redirects guests to login', function (): void {
    $this->fromRoute('home')
        ->get('/some-team/dashboard')
        ->assertRedirectToRoute('login');
});
