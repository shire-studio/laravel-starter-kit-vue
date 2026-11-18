<?php

declare(strict_types=1);

use App\Http\Middleware\SetTeamUrlDefaults;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    Route::middleware(['web', SetTeamUrlDefaults::class])->get('{current_team}/defaults', fn (): string => 'ok')->name('defaults.current');
    Route::middleware(['web', SetTeamUrlDefaults::class])->get('defaults/{team}', fn (): string => 'ok')->name('defaults.team');
    Route::getRoutes()->refreshNameLookups();
    Route::middleware(['web', SetTeamUrlDefaults::class])->get('open', fn (): string => 'ok');
});

it('sets url defaults to the current team', function (): void {
    $user = User::factory()->create();
    $team = $user->currentTeamOrPersonal();

    $this->actingAs($user)->get('open')->assertOk();

    expect(route('defaults.current'))->toEndWith("/{$team->slug}/defaults")
        ->and(route('defaults.team'))->toEndWith("/defaults/{$team->slug}");
});

it('sets no defaults for guests', function (): void {
    URL::defaults(['current_team' => null, 'current_team:slug' => null, 'team' => null, 'team:slug' => null]);

    $this->get('open')->assertOk();

    expect(URL::getDefaultParameters())->toBe(['current_team' => null, 'current_team:slug' => null, 'team' => null, 'team:slug' => null]);
});

it('sets no defaults for users without a team', function (): void {
    URL::defaults(['current_team' => null, 'current_team:slug' => null, 'team' => null, 'team:slug' => null]);
    $user = User::factory()->create();
    $user->teams()->detach();
    $user->forceFill(['current_team_id' => null])->save();

    $this->actingAs($user)->get('open')->assertOk();

    expect(URL::getDefaultParameters())->toBe(['current_team' => null, 'current_team:slug' => null, 'team' => null, 'team:slug' => null]);
});

it('resolves slug bound routes without parameters', function (): void {
    Route::middleware(['web', SetTeamUrlDefaults::class])->get('{current_team:slug}/bound', fn (): string => 'ok')->name('bound.current');
    Route::middleware(['web', SetTeamUrlDefaults::class])->get('bound/{team:slug}', fn (): string => 'ok')->name('bound.team');
    Route::getRoutes()->refreshNameLookups();
    $user = User::factory()->create();
    $team = $user->currentTeamOrPersonal();

    $this->actingAs($user)->get('open')->assertOk();

    expect(route('bound.current'))->toBe(url("/{$team->slug}/bound"))
        ->and(route('bound.team'))->toBe(url("/bound/{$team->slug}"));
});
