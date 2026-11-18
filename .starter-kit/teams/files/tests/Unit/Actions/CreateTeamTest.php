<?php

declare(strict_types=1);

use App\Actions\CreateTeam;
use App\Enums\TeamRole;
use App\Models\User;

it('creates a team owned by the user and switches to it', function (): void {
    $user = User::factory()->create();

    $team = resolve(CreateTeam::class)->handle($user, ['name' => 'Acme Inc'])->refresh();

    expect($team->name)->toBe('Acme Inc')
        ->and($team->slug)->toBe('acme-inc')
        ->and($team->is_personal)->toBeFalse()
        ->and($user->teamRole($team))->toBe(TeamRole::Owner)
        ->and($user->fresh()?->current_team_id)->toBe($team->id);
});

it('creates a personal team', function (): void {
    $user = User::factory()->withoutPersonalTeam()->create();

    $team = resolve(CreateTeam::class)->handle($user, ['name' => "{$user->name}'s Team"], personal: true)->refresh();

    expect($team->is_personal)->toBeTrue()
        ->and($user->personalTeam()?->is($team))->toBeTrue();
});
