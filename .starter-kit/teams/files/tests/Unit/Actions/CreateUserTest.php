<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use App\Enums\TeamRole;
use Illuminate\Support\Facades\Hash;

it('may create a user', function (): void {
    $action = resolve(CreateUser::class);

    $user = $action->handle([
        'name' => 'Test User',
        'email' => 'example@email.com',
    ], 'password')->refresh();

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('example@email.com')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('creates a personal team for the user', function (): void {
    $user = resolve(CreateUser::class)->handle([
        'name' => 'Test User',
        'email' => 'example@email.com',
    ], 'password')->refresh();

    $team = $user->personalTeam();

    expect($team?->name)->toBe("Test User's Team")
        ->and($team?->is_personal)->toBeTrue()
        ->and($user->teamRole($team ?? throw new RuntimeException()))->toBe(TeamRole::Owner)
        ->and($user->current_team_id)->toBe($team->id);
});

it('keeps the personal team name and slug within their columns', function (): void {
    $user = resolve(CreateUser::class)->handle([
        'name' => str_repeat('@', 255),
        'email' => 'example@email.com',
    ], 'password')->refresh();

    $team = $user->personalTeam();

    expect(mb_strlen($team?->name ?? ''))->toBeLessThanOrEqual(255)
        ->and(mb_strlen($team?->slug ?? ''))->toBeLessThanOrEqual(255);
});
