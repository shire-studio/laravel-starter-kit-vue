<?php

declare(strict_types=1);

use App\Models\User;

it('serializes only visible attributes', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'email_verified_at',
            'two_factor_confirmed_at',
            'created_at',
            'updated_at',
            'current_team_id',
        ]);
});

it('has a personal team', function (): void {
    $user = User::factory()->create();

    expect($user->personalTeam())->not->toBeNull()
        ->and($user->personalTeam()?->name)->toBe("{$user->name}'s Team")
        ->and($user->current_team_id)->toBe($user->personalTeam()?->id);
});
