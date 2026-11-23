<?php

declare(strict_types=1);

use App\Actions\CreateTeam;
use App\Actions\DeleteTeam;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

it('deletes the team, its memberships and invitations', function (): void {
    $user = User::factory()->create();
    $team = resolve(CreateTeam::class)->handle($user, ['name' => 'Acme']);
    TeamInvitation::factory()->create(['team_id' => $team->id, 'invited_by' => $user->id]);

    resolve(DeleteTeam::class)->handle($team, $user);

    expect(Team::query()->find($team->id))->toBeNull()
        ->and(Team::withTrashed()->find($team->id))->not->toBeNull()
        ->and(Membership::query()->where('team_id', $team->id)->count())->toBe(0)
        ->and(TeamInvitation::query()->where('team_id', $team->id)->count())->toBe(0);
});

it('moves members whose current team it was to their personal team', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = resolve(CreateTeam::class)->handle($owner, ['name' => 'Acme']);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->forceFill(['current_team_id' => $team->id])->save();

    resolve(DeleteTeam::class)->handle($team, $owner);

    expect($member->fresh()?->current_team_id)->toBe($member->personalTeam()?->id);
});

it('switches the deleting user to their personal team', function (): void {
    $user = User::factory()->create(['name' => 'Zed']);
    Team::factory()->create(['name' => 'Aardvark'])->members()->attach($user, ['role' => TeamRole::Member->value]);
    $team = resolve(CreateTeam::class)->handle($user, ['name' => 'Acme']);

    resolve(DeleteTeam::class)->handle($team, $user);

    expect($user->fresh()?->current_team_id)->toBe($user->personalTeam()?->id);
});

it('leaves the current team of the deleting user alone when another team was current', function (): void {
    $user = User::factory()->create();
    $other = Team::factory()->create();
    $other->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $currentBefore = $user->current_team_id;

    resolve(DeleteTeam::class)->handle($other, $user);

    expect($user->fresh()?->current_team_id)->toBe($currentBefore);
});
