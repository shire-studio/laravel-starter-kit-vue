<?php

declare(strict_types=1);

use App\Actions\AcceptTeamInvitation;
use App\Enums\TeamRole;
use App\Models\TeamInvitation;
use App\Models\User;

it('joins the team with the invited role and switches to it', function (): void {
    $user = User::factory()->create();
    $invitation = TeamInvitation::factory()->create(['role' => TeamRole::Admin]);

    $team = resolve(AcceptTeamInvitation::class)->handle($invitation, $user);

    expect($team->is($invitation->team))->toBeTrue()
        ->and($user->teamRole($team))->toBe(TeamRole::Admin)
        ->and($user->fresh()?->current_team_id)->toBe($team->id)
        ->and($invitation->fresh()?->accepted_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('keeps an existing membership', function (): void {
    $user = User::factory()->create();
    $invitation = TeamInvitation::factory()->create(['role' => TeamRole::Member]);
    $invitation->team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    resolve(AcceptTeamInvitation::class)->handle($invitation, $user);

    expect($user->teamRole($invitation->team))->toBe(TeamRole::Admin)
        ->and($invitation->team->memberships()->count())->toBe(1);
});
