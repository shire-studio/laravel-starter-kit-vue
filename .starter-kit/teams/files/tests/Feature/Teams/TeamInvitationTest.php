<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;

function inviterSetup(TeamRole $role = TeamRole::Owner): array
{
    $team = Team::factory()->create(['name' => 'Acme']);
    $user = User::factory()->create(['name' => 'Olivia Owner']);
    $team->members()->attach($user, ['role' => $role->value]);

    test()->actingAs($user);

    return [$team, $user];
}

it('invites a member by email', function (): void {
    Notification::fake();

    [$team, $user] = inviterSetup();

    $this->fromRoute('teams.edit', $team)
        ->post(route('teams.invitations.store', $team), ['email' => 'new@example.com', 'role' => 'admin'])
        ->assertRedirectToRoute('teams.edit', $team)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invitation sent.']);

    $invitation = TeamInvitation::query()->where('email', 'new@example.com')->firstOrFail();

    expect($invitation->team_id)->toBe($team->id)
        ->and($invitation->role)->toBe(TeamRole::Admin)
        ->and($invitation->invited_by)->toBe($user->id);

    Notification::assertSentOnDemand(
        TeamInvitationNotification::class,
        fn (TeamInvitationNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'new@example.com',
    );
});

it('rejects inviting an existing member', function (): void {
    [$team, $user] = inviterSetup();

    $this->fromRoute('teams.edit', $team)
        ->post(route('teams.invitations.store', $team), ['email' => $user->email, 'role' => 'member'])
        ->assertRedirectToRoute('teams.edit', $team)
        ->assertSessionHasErrors('email');
});

it('rejects a duplicate pending invitation', function (): void {
    [$team, $user] = inviterSetup();

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $user->id,
        'email' => 'new@example.com',
    ]);

    $this->fromRoute('teams.edit', $team)
        ->post(route('teams.invitations.store', $team), ['email' => 'new@example.com', 'role' => 'member'])
        ->assertRedirectToRoute('teams.edit', $team)
        ->assertSessionHasErrors('email');
});

it('rejects an invalid invitation email', function (): void {
    [$team] = inviterSetup();

    $this->fromRoute('teams.edit', $team)
        ->post(route('teams.invitations.store', $team), ['email' => 'not-an-email', 'role' => 'member'])
        ->assertRedirectToRoute('teams.edit', $team)
        ->assertSessionHasErrors('email');
});

it('rejects inviting with the owner role', function (): void {
    [$team] = inviterSetup();

    $this->fromRoute('teams.edit', $team)
        ->post(route('teams.invitations.store', $team), ['email' => 'new@example.com', 'role' => 'owner'])
        ->assertRedirectToRoute('teams.edit', $team)
        ->assertSessionHasErrors('role');
});

it('forbids members inviting', function (): void {
    [$team] = inviterSetup(TeamRole::Member);

    $this->post(route('teams.invitations.store', $team), ['email' => 'new@example.com', 'role' => 'member'])
        ->assertNotFound();
});

it('cancels an invitation', function (): void {
    [$team, $user] = inviterSetup();

    $invitation = TeamInvitation::factory()->create(['team_id' => $team->id, 'invited_by' => $user->id]);

    $this->fromRoute('teams.edit', $team)
        ->delete(route('teams.invitations.destroy', [$team, $invitation]))
        ->assertRedirectToRoute('teams.edit', $team)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invitation cancelled.']);

    expect(TeamInvitation::query()->find($invitation->id))->toBeNull();
});

it('forbids members cancelling an invitation', function (): void {
    [$team, $user] = inviterSetup(TeamRole::Member);

    $invitation = TeamInvitation::factory()->create(['team_id' => $team->id, 'invited_by' => $user->id]);

    $this->delete(route('teams.invitations.destroy', [$team, $invitation]))->assertNotFound();
});

it('returns 404 for an invitation from another team', function (): void {
    [$team] = inviterSetup();

    $invitation = TeamInvitation::factory()->create();

    $this->delete(route('teams.invitations.destroy', [$team, $invitation]))->assertNotFound();

    expect(TeamInvitation::query()->find($invitation->id))->not->toBeNull();
});

it('returns 404 when inviting to a personal team', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('teams.invitations.store', $user->personalTeam()), ['email' => 'new@example.com', 'role' => 'member'])
        ->assertNotFound();
});

it('still cancels an invitation on a personal team', function (): void {
    $user = User::factory()->create();
    $team = $user->personalTeam();
    $invitation = TeamInvitation::factory()->create(['team_id' => $team->id, 'invited_by' => $user->id]);

    $this->actingAs($user)
        ->fromRoute('teams.edit', $team)
        ->delete(route('teams.invitations.destroy', [$team, $invitation]))
        ->assertRedirectToRoute('teams.edit', $team);

    expect(TeamInvitation::query()->find($invitation->id))->toBeNull();
});
