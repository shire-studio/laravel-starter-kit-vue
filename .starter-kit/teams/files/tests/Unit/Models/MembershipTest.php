<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;

it('casts the role and uses a uuid key', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->withoutPersonalTeam()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $membership = Membership::query()->firstOrFail();

    expect($membership->role)->toBe(TeamRole::Owner)
        ->and($membership->id)->toBeString()->toHaveLength(36)
        ->and($membership->team_id)->toBe($team->id)
        ->and($membership->user_id)->toBe($user->id);
});

it('has a factory for members', function (): void {
    $membership = Membership::factory()->create()->refresh();

    expect($membership->role)->toBe(TeamRole::Member)
        ->and($membership->team->members()->first()?->is($membership->user))->toBeTrue();
});
