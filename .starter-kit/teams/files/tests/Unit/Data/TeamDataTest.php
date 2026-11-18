<?php

declare(strict_types=1);

use App\Data\TeamData;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('can be created from a team and viewer', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    expect(TeamData::fromTeam($team, $user)->toArray())->toBe([
        'id' => $team->id,
        'name' => 'Acme',
        'slug' => 'acme',
        'is_personal' => false,
        'role' => 'admin',
        'role_label' => 'Admin',
        'is_current' => false,
        'can_leave' => true,
        'can_update' => true,
    ]);
});

it('takes what the viewer may do from the team policy', function (TeamRole $role, bool $canLeave, bool $canUpdate): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);

    $data = TeamData::fromTeam($team, $user);

    expect($data->can_leave)->toBe($canLeave)
        ->and($data->can_update)->toBe($canUpdate);
})->with([
    'owner' => [TeamRole::Owner, false, true],
    'admin' => [TeamRole::Admin, true, true],
    'member' => [TeamRole::Member, true, false],
]);

it('never lets the owner leave a personal team', function (): void {
    $user = User::factory()->create();

    $data = TeamData::fromTeam($user->personalTeam(), $user);

    expect($data->can_leave)->toBeFalse()
        ->and($data->can_update)->toBeTrue();
});

it('has no role or abilities for a non member', function (): void {
    $team = Team::factory()->create();

    $data = TeamData::fromTeam($team, User::factory()->create());

    expect($data->role)->toBeNull()
        ->and($data->role_label)->toBeNull()
        ->and($data->can_leave)->toBeFalse()
        ->and($data->can_update)->toBeFalse();
});

it('reads a team listed through the viewer without a query', function (): void {
    $user = User::factory()->create();
    $team = $user->teams()->firstOrFail();

    DB::enableQueryLog();

    TeamData::fromTeam($team, $user);

    expect(DB::getQueryLog())->toBeEmpty();
});
