<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

it('accepts an invitation', function (): void {
    $user = User::factory()->create(['email' => 'me@example.com']);
    $invitation = TeamInvitation::factory()->create(['email' => 'me@example.com', 'role' => TeamRole::Admin]);

    $this->actingAs($user)
        ->fromRoute('team.dashboard', ['current_team' => $user->personalTeam()?->slug])
        ->post(route('invitations.accept', $invitation))
        ->assertRedirectToRoute('dashboard')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invitation accepted.']);

    expect($user->teamRole($invitation->team))->toBe(TeamRole::Admin)
        ->and($user->fresh()->current_team_id)->toBe($invitation->team_id)
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
});

it('declines an invitation', function (): void {
    $user = User::factory()->create(['email' => 'me@example.com']);
    $invitation = TeamInvitation::factory()->create(['email' => 'me@example.com']);

    $this->actingAs($user)
        ->fromRoute('team.dashboard', ['current_team' => $user->personalTeam()?->slug])
        ->delete(route('invitations.decline', $invitation))
        ->assertRedirectToRoute('dashboard')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invitation declined.']);

    expect(TeamInvitation::query()->find($invitation->id))->toBeNull()
        ->and($user->belongsToTeam($invitation->team))->toBeFalse();
});

it('rejects an invitation for another email', function (): void {
    $invitation = TeamInvitation::factory()->create(['email' => 'other@example.com']);
    $user = User::factory()->create(['email' => 'me@example.com']);

    $this->actingAs($user)
        ->fromRoute('team.dashboard', ['current_team' => $user->personalTeam()?->slug])
        ->post(route('invitations.accept', $invitation))
        ->assertRedirectToRoute('team.dashboard', ['current_team' => $user->personalTeam()?->slug])
        ->assertSessionHasErrors('invitation');

    expect($invitation->fresh()->accepted_at)->toBeNull();
});

it('rejects declining an invitation for another email', function (): void {
    $invitation = TeamInvitation::factory()->create(['email' => 'other@example.com']);
    $user = User::factory()->create(['email' => 'me@example.com']);

    $this->actingAs($user)
        ->fromRoute('team.dashboard', ['current_team' => $user->personalTeam()?->slug])
        ->delete(route('invitations.decline', $invitation))
        ->assertRedirectToRoute('team.dashboard', ['current_team' => $user->personalTeam()?->slug])
        ->assertSessionHasErrors('invitation');

    expect(TeamInvitation::query()->find($invitation->id))->not->toBeNull();
});

it('rejects an expired invitation', function (): void {
    $invitation = TeamInvitation::factory()->expired()->create(['email' => 'me@example.com']);
    $user = User::factory()->create(['email' => 'me@example.com']);

    $this->actingAs($user)
        ->fromRoute('team.dashboard', ['current_team' => $user->personalTeam()?->slug])
        ->post(route('invitations.accept', $invitation))
        ->assertSessionHasErrors('invitation');

    expect($invitation->fresh()->accepted_at)->toBeNull();
});

it('rejects accepting an invitation to a personal team', function (): void {
    $user = User::factory()->create(['email' => 'me@example.com']);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => Team::factory()->personal()->create()->id,
        'email' => 'me@example.com',
    ]);

    $this->actingAs($user)
        ->post(route('invitations.accept', $invitation))
        ->assertSessionHasErrors('invitation');

    expect($invitation->fresh()->accepted_at)->toBeNull();
});

it('declines an invitation to a personal team', function (): void {
    $user = User::factory()->create(['email' => 'me@example.com']);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => Team::factory()->personal()->create()->id,
        'email' => 'me@example.com',
    ]);

    $this->actingAs($user)
        ->fromRoute('team.dashboard', ['current_team' => $user->personalTeam()?->slug])
        ->delete(route('invitations.decline', $invitation))
        ->assertRedirectToRoute('dashboard')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invitation declined.']);

    expect(TeamInvitation::query()->find($invitation->id))->toBeNull();
});
