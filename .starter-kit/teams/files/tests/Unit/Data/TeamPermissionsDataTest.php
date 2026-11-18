<?php

declare(strict_types=1);

use App\Data\TeamPermissionsData;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('reflects owner permissions', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    expect(TeamPermissionsData::fromTeam($team, $user)->toArray())->toBe([
        'can_update_team' => true,
        'can_delete_team' => true,
        'can_add_member' => true,
        'can_update_member' => true,
        'can_remove_member' => true,
        'can_create_invitation' => true,
        'can_cancel_invitation' => true,
    ]);
});

it('reflects admin permissions', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    expect(TeamPermissionsData::fromTeam($team, $user)->toArray())->toBe([
        'can_update_team' => true,
        'can_delete_team' => false,
        'can_add_member' => false,
        'can_update_member' => false,
        'can_remove_member' => false,
        'can_create_invitation' => true,
        'can_cancel_invitation' => true,
    ]);
});

it('gives members no permissions', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect(array_filter(TeamPermissionsData::fromTeam($team, $user)->toArray()))->toBeEmpty();
});

it('never lets the owner delete a personal team', function (): void {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    assert($team instanceof Team);

    $permissions = TeamPermissionsData::fromTeam($team, $user);

    expect($permissions->can_delete_team)->toBeFalse()
        ->and($permissions->can_update_team)->toBeTrue();
});

it('asks the policy with a single query for the viewer membership', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    DB::enableQueryLog();

    TeamPermissionsData::fromTeam($team, $user);

    expect(DB::getQueryLog())->toHaveCount(1);
});

it('asks the policy without a query for a team listed through the viewer', function (): void {
    $user = User::factory()->create();
    $team = $user->teams()->firstOrFail();

    DB::enableQueryLog();

    TeamPermissionsData::fromTeam($team, $user);

    expect(DB::getQueryLog())->toBeEmpty();
});

it('denies everything to a non member', function (): void {
    $permissions = TeamPermissionsData::fromTeam(Team::factory()->create(), User::factory()->create());

    expect(array_filter($permissions->toArray()))->toBeEmpty();
});

it('never lets anyone add members or invite to a personal team', function (): void {
    $user = User::factory()->create();
    $permissions = TeamPermissionsData::fromTeam($user->personalTeam(), $user);

    expect($permissions->can_add_member)->toBeFalse()
        ->and($permissions->can_create_invitation)->toBeFalse()
        ->and($permissions->can_cancel_invitation)->toBeTrue()
        ->and($permissions->can_update_team)->toBeTrue();
});
