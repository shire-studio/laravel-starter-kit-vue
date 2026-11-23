<?php

declare(strict_types=1);

use App\Actions\LeaveTeam;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

it('leaves the team and switches to the personal team', function (): void {
    $user = User::factory()->create(['name' => 'Zed']);
    Team::factory()->create(['name' => 'Aardvark'])->members()->attach($user, ['role' => TeamRole::Member->value]);
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    resolve(LeaveTeam::class)->handle($team, $user);

    expect($user->belongsToTeam($team))->toBeFalse()
        ->and($user->fresh()?->current_team_id)->toBe($user->personalTeam()?->id);
});

it('keeps the current team when leaving another team', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $current = $user->current_team_id;

    resolve(LeaveTeam::class)->handle($team, $user);

    expect($user->fresh()?->current_team_id)->toBe($current);
});
