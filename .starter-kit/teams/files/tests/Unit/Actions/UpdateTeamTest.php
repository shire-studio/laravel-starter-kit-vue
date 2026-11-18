<?php

declare(strict_types=1);

use App\Actions\UpdateTeam;
use App\Models\Team;

it('renames the team and its slug', function (): void {
    $team = Team::factory()->create(['name' => 'Old', 'slug' => 'old']);

    $updated = resolve(UpdateTeam::class)->handle($team, ['name' => 'New Name'])->refresh();

    expect($updated->name)->toBe('New Name')
        ->and($updated->slug)->toBe('new-name');
});

it('keeps the slug when the name is unchanged', function (): void {
    $team = Team::factory()->create(['name' => 'Same', 'slug' => 'custom']);

    resolve(UpdateTeam::class)->handle($team, ['name' => 'Same']);

    expect($team->fresh()?->slug)->toBe('custom');
});
