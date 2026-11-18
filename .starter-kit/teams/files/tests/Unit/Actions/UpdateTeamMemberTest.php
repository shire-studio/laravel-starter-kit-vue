<?php

declare(strict_types=1);

use App\Actions\UpdateTeamMember;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

it('updates the role of a member', function (): void {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    resolve(UpdateTeamMember::class)->handle($team, $member, ['role' => 'admin']);

    expect($member->teamRole($team))->toBe(TeamRole::Admin);
});
