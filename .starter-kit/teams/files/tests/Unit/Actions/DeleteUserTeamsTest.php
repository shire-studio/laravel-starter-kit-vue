<?php

declare(strict_types=1);

use App\Actions\CreateTeam;
use App\Actions\DeleteUserTeams;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

it('deletes owned teams', function (): void {
    $user = User::factory()->create();
    $team = resolve(CreateTeam::class)->handle($user, ['name' => 'Acme']);
    $personal = $user->personalTeam();

    resolve(DeleteUserTeams::class)->handle($user);

    expect(Team::query()->find($team->id))->toBeNull()
        ->and(Team::query()->find($personal?->id))->toBeNull()
        ->and($user->fresh()?->current_team_id)->toBeNull();
});

it('moves members of deleted teams to their personal team', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = resolve(CreateTeam::class)->handle($owner, ['name' => 'Acme']);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->forceFill(['current_team_id' => $team->id])->save();

    resolve(DeleteUserTeams::class)->handle($owner);

    expect($member->fresh()?->current_team_id)->toBe($member->personalTeam()?->id);
});

it('removes memberships and sent invitations', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    TeamInvitation::factory()->create(['team_id' => $team->id, 'invited_by' => $user->id]);

    resolve(DeleteUserTeams::class)->handle($user);

    expect(Membership::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(TeamInvitation::query()->where('invited_by', $user->id)->count())->toBe(0);
});
