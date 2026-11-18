<?php

declare(strict_types=1);

use App\Actions\SwitchTeam;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

it('switches the current team', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    resolve(SwitchTeam::class)->handle($user, $team);

    expect($user->fresh()?->current_team_id)->toBe($team->id);
});

it('refreshes an already loaded current team relation', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->currentTeamOrPersonal()?->id)->toBe($user->personalTeam()?->id);

    resolve(SwitchTeam::class)->handle($user, $team);

    expect($user->currentTeamOrPersonal()?->id)->toBe($team->id);
});
