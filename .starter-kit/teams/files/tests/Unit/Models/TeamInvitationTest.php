<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

it('is pending until accepted or expired', function (): void {
    $pending = TeamInvitation::factory()->create();
    $expired = TeamInvitation::factory()->expired()->create();
    $accepted = TeamInvitation::factory()->accepted()->create();

    expect($pending->isPending())->toBeTrue()
        ->and($pending->isExpired())->toBeFalse()
        ->and($pending->isAccepted())->toBeFalse()
        ->and($expired->isPending())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue()
        ->and($accepted->isPending())->toBeFalse()
        ->and($accepted->isAccepted())->toBeTrue();
});

it('never expires without an expiry date', function (): void {
    $invitation = TeamInvitation::factory()->create(['expires_at' => null]);

    expect($invitation->isExpired())->toBeFalse()
        ->and($invitation->isPending())->toBeTrue();
});

it('scopes pending invitations', function (): void {
    $pending = TeamInvitation::factory()->create();
    $open = TeamInvitation::factory()->create(['expires_at' => null]);
    TeamInvitation::factory()->expired()->create();
    TeamInvitation::factory()->accepted()->create();

    expect(TeamInvitation::query()->pending()->pluck('id')->all())
        ->toEqualCanonicalizing([$pending->id, $open->id]);
});

it('scopes pending invitations a user can join, latest first', function (): void {
    $user = User::factory()->create(['email' => 'me@example.com']);
    $older = TeamInvitation::factory()->create(['email' => 'ME@example.com', 'created_at' => now()->subDay()]);
    $newer = TeamInvitation::factory()->create(['email' => 'me@example.com']);
    TeamInvitation::factory()->expired()->create(['email' => 'me@example.com']);
    TeamInvitation::factory()->accepted()->create(['email' => 'me@example.com']);
    TeamInvitation::factory()->for(Team::factory()->personal())->create(['email' => 'me@example.com']);
    TeamInvitation::factory()->create(['email' => 'other@example.com']);

    $invitations = TeamInvitation::query()->pendingFor($user)->get();

    expect($invitations->pluck('id')->all())->toBe([$newer->id, $older->id])
        ->and($invitations->first()?->relationLoaded('team'))->toBeTrue()
        ->and($invitations->first()?->relationLoaded('inviter'))->toBeTrue();
});

it('relates to its team and inviter and routes by code', function (): void {
    $team = Team::factory()->create();
    $inviter = User::factory()->withoutPersonalTeam()->create();
    $invitation = TeamInvitation::factory()->for($team)->create([
        'invited_by' => $inviter->id,
        'role' => TeamRole::Admin,
    ]);

    expect($invitation->team->is($team))->toBeTrue()
        ->and($invitation->inviter->is($inviter))->toBeTrue()
        ->and($invitation->role)->toBe(TeamRole::Admin)
        ->and($invitation->code)->toHaveLength(64)
        ->and($invitation->getRouteKeyName())->toBe('code');
});
