<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Rules\OwnsNoSharedTeams;
use Illuminate\Support\Facades\Validator;

function ownsNoSharedTeamsValidator(User $user): Illuminate\Validation\Validator
{
    return Validator::make(['password' => 'password'], ['password' => [new OwnsNoSharedTeams($user)]]);
}

it('passes when the user only owns a personal team', function (): void {
    expect(ownsNoSharedTeamsValidator(User::factory()->create())->passes())->toBeTrue();
});

it('passes when no one else belongs to the teams the user owns', function (): void {
    $user = User::factory()->create();
    Team::factory()->create()->members()->attach($user, ['role' => TeamRole::Owner->value]);

    expect(ownsNoSharedTeamsValidator($user)->passes())->toBeTrue();
});

it('passes when the user is only a member of a shared team', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach(User::factory()->create(), ['role' => TeamRole::Owner->value]);
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect(ownsNoSharedTeamsValidator($user)->passes())->toBeTrue();
});

it('fails when the user owns a team with other members', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $team->members()->attach(User::factory()->create(), ['role' => TeamRole::Member->value]);

    $validator = ownsNoSharedTeamsValidator($user);

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('password'))
        ->toBe('Remove the other members or delete the teams you own before deleting your account.');
});
