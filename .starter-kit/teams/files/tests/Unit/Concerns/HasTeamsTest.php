<?php

declare(strict_types=1);

use App\Actions\UpdateTeamMember;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('creates a personal team for factory users', function (): void {
    $user = User::factory()->create(['name' => 'Frodo']);

    $team = $user->personalTeam();

    expect($team?->name)->toBe("Frodo's Team")
        ->and($team?->is_personal)->toBeTrue()
        ->and($user->refresh()->current_team_id)->toBe($team?->id)
        ->and($user->currentTeam->is($team))->toBeTrue()
        ->and($user->ownsTeam($team))->toBeTrue()
        ->and($user->ownedTeams()->count())->toBe(1);
});

it('caps the personal team name to the column length', function (): void {
    $user = User::factory()->make(['name' => str_repeat('a', 247).' '.str_repeat('b', 7)]);

    expect($user->personalTeamName())->toBe(str_repeat('a', 247)."'s Team")
        ->and($user->personalTeamName())->toHaveLength(254);

    $user->name = str_repeat('a', 255);

    expect($user->personalTeamName())->toBe(str_repeat('a', 248)."'s Team")
        ->and($user->personalTeamName())->toHaveLength(255);
});

it('skips the personal team when asked', function (): void {
    $user = User::factory()->withoutPersonalTeam()->create();

    expect($user->personalTeam())->toBeNull()
        ->and($user->current_team_id)->toBeNull()
        ->and($user->currentTeamOrPersonal())->toBeNull();
});

it('keeps skipping the personal team through further states', function (): void {
    $user = User::factory()->withoutPersonalTeam()->unverified()->create();

    expect($user->teams()->count())->toBe(0);
});

it('knows team membership and roles', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $other = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    expect($user->belongsToTeam($team))->toBeTrue()
        ->and($user->belongsToTeam($other))->toBeFalse()
        ->and($user->teamRole($team))->toBe(TeamRole::Admin)
        ->and($user->teamRole($other))->toBeNull()
        ->and($user->ownsTeam($team))->toBeFalse()
        ->and($user->isCurrentTeam($team))->toBeFalse()
        ->and($user->isCurrentTeam($user->personalTeam()))->toBeTrue()
        ->and($user->teamMemberships()->count())->toBe(2);
});

it('checks permissions through the role', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    expect($user->hasTeamPermission($team, TeamPermission::UpdateTeam))->toBeTrue()
        ->and($user->hasTeamPermission($team, TeamPermission::DeleteTeam))->toBeFalse()
        ->and($user->hasTeamPermission(Team::factory()->create(), TeamPermission::UpdateTeam))->toBeFalse();
});

it('falls back to the personal team when no current team is set', function (): void {
    $user = User::factory()->create();
    $personal = $user->personalTeam();
    $user->forceFill(['current_team_id' => null])->save();
    $user->unsetRelation('currentTeam');

    expect($user->currentTeamOrPersonal()?->is($personal))->toBeTrue();

    $other = Team::factory()->create();
    $other->members()->attach($user, ['role' => TeamRole::Member->value]);
    $user->forceFill(['current_team_id' => $other->id])->save();
    $user->unsetRelation('currentTeam');

    expect($user->currentTeamOrPersonal()?->is($other))->toBeTrue();
});

it('falls back to the personal team when the user no longer belongs to the current team', function (): void {
    $user = User::factory()->create();
    $personal = $user->personalTeam();
    $other = Team::factory()->create();
    $user->forceFill(['current_team_id' => $other->id])->save();
    $user->unsetRelation('currentTeam');

    expect($user->currentTeamOrPersonal()?->is($personal))->toBeTrue();
});

it('reads a membership loaded through either relation without a query', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);
    $listedTeam = $user->teams()->whereKey($team->id)->firstOrFail();
    $listedMember = $team->members()->whereKey($user->id)->firstOrFail();

    DB::enableQueryLog();

    expect($user->teamRole($listedTeam))->toBe(TeamRole::Admin)
        ->and($user->belongsToTeam($listedTeam))->toBeTrue()
        ->and($listedMember->teamRole($team))->toBe(TeamRole::Admin)
        ->and($listedMember->belongsToTeam($team))->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('ignores a loaded membership of another user or team', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $listedByAdmin = $admin->teams()->whereKey($team->id)->firstOrFail();
    $listedOwner = $team->members()->whereKey($owner->id)->firstOrFail();

    expect($owner->teamRole($listedByAdmin))->toBe(TeamRole::Owner)
        ->and($listedOwner->teamRole($owner->personalTeam()))->toBe(TeamRole::Owner)
        ->and($listedOwner->belongsToTeam(Team::factory()->create()))->toBeFalse();
});

it('loads a team with the membership once', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $loaded = $user->teamWithMembership($team);

    DB::enableQueryLog();

    expect($loaded->is($team))->toBeTrue()
        ->and($user->teamWithMembership($loaded))->toBe($loaded)
        ->and($user->teamRole($loaded))->toBe(TeamRole::Member)
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('keeps a team without a membership as it is', function (): void {
    $team = Team::factory()->create();

    expect(User::factory()->create()->teamWithMembership($team))->toBe($team);
});

it('reads a changed role on the next lookup', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->teamRole($team))->toBe(TeamRole::Member);

    resolve(UpdateTeamMember::class)->handle($team, $user, ['role' => TeamRole::Admin->value]);

    expect($user->teamRole($team))->toBe(TeamRole::Admin)
        ->and($user->teamRole($user->teamWithMembership($team)))->toBe(TeamRole::Admin)
        ->and($user->can('update', $team))->toBeTrue();
});
