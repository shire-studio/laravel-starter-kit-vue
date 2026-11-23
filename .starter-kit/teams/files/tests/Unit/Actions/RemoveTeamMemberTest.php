<?php

declare(strict_types=1);

use App\Actions\RemoveTeamMember;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

it('removes the member', function (): void {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    resolve(RemoveTeamMember::class)->handle($team, $member);

    expect($member->belongsToTeam($team))->toBeFalse();
});

it('moves the member to their personal team when it was current', function (): void {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->forceFill(['current_team_id' => $team->id])->save();

    resolve(RemoveTeamMember::class)->handle($team, $member);

    expect($member->fresh()?->current_team_id)->toBe($member->personalTeam()?->id);
});
