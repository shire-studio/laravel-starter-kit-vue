<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

function backfillMigration(): object
{
    return require database_path('migrations/2026_10_25_000002_create_personal_teams_for_existing_users.php');
}

it('creates personal teams for existing users', function (): void {
    $users = User::factory()->withoutPersonalTeam()->count(2)->create();

    backfillMigration()->up();

    foreach ($users as $user) {
        $team = $user->fresh()?->personalTeam();

        expect($team?->name)->toBe("{$user->name}'s Team")
            ->and($user->fresh()?->current_team_id)->toBe($team?->id);
    }

    expect(Team::query()->count())->toBe(2);
});

it('leaves users who already have a team alone', function (): void {
    User::factory()->create();

    backfillMigration()->up();

    expect(Team::query()->count())->toBe(1);
});
