<?php

declare(strict_types=1);

use App\Http\Controllers\CurrentTeamDashboardController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('web')->get('{current_team}/dashboard', fn (): JsonResponse => response()->json(['toast' => session('toast')]))->name('team.dashboard');
    Route::getRoutes()->refreshNameLookups();
    Route::middleware(['web', 'auth'])->get('probe-dashboard', CurrentTeamDashboardController::class);
});

it('redirects to the current team dashboard', function (): void {
    $user = User::factory()->create();
    $team = $user->currentTeamOrPersonal();

    $this->actingAs($user)->get('probe-dashboard')
        ->assertRedirect(route('team.dashboard', ['current_team' => $team->slug]));
});

it('returns not found when the user has no team', function (): void {
    $user = User::factory()->create();
    $user->teams()->detach();
    $user->forceFill(['current_team_id' => null])->save();

    $this->actingAs($user)->get('probe-dashboard')->assertNotFound();
});

it('keeps flashed data for the team dashboard', function (): void {
    Route::middleware('web')->get('probe-flash', fn (): RedirectResponse => redirect('probe-dashboard')->with('toast', 'Saved.'));

    $this->actingAs(User::factory()->create())
        ->followingRedirects()
        ->get('probe-flash')
        ->assertJson(['toast' => 'Saved.']);
});
