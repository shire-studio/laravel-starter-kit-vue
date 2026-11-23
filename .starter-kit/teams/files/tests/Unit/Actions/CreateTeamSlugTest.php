<?php

declare(strict_types=1);

use App\Actions\CreateTeamSlug;
use App\Models\Team;

it('slugs the name', function (): void {
    expect(resolve(CreateTeamSlug::class)->handle('Acme Inc'))->toBe('acme-inc');
});

it('suffixes a taken slug', function (): void {
    Team::factory()->create(['slug' => 'acme']);
    Team::factory()->create(['slug' => 'acme-1']);
    Team::factory()->create(['slug' => 'acme-2']);
    Team::factory()->create(['slug' => 'acme-corp']);

    expect(resolve(CreateTeamSlug::class)->handle('Acme'))->toBe('acme-3');
});

it('suffixes a single taken slug', function (): void {
    Team::factory()->create(['slug' => 'acme']);

    expect(resolve(CreateTeamSlug::class)->handle('Acme'))->toBe('acme-1');
});

it('counts soft deleted teams', function (): void {
    Team::factory()->create(['slug' => 'acme'])->delete();

    expect(resolve(CreateTeamSlug::class)->handle('Acme'))->toBe('acme-1');
});

it('ignores the given team', function (): void {
    $team = Team::factory()->create(['slug' => 'acme']);

    expect(resolve(CreateTeamSlug::class)->handle('Acme', $team))->toBe('acme');
});

it('falls back when the name has no slug characters', function (): void {
    expect(resolve(CreateTeamSlug::class)->handle('!!!'))->toBe('team');

    Team::factory()->create(['slug' => 'team']);

    expect(resolve(CreateTeamSlug::class)->handle('!!!'))->toBe('team-1');
});

it('caps the slug so a suffix still fits the column', function (): void {
    $name = str_repeat('@', 255);

    $slug = resolve(CreateTeamSlug::class)->handle($name);

    expect(mb_strlen($slug))->toBeLessThanOrEqual(240)
        ->and($slug)->toStartWith('at-at')
        ->and($slug)->not->toEndWith('-');

    Team::factory()->create(['slug' => $slug]);

    expect(resolve(CreateTeamSlug::class)->handle($name))->toBe($slug.'-1');
});
