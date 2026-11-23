<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

it('switches teams and stays on the previous page', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->fromRoute('teams.index')
        ->post(route('teams.switch', $team))
        ->assertRedirectToRoute('teams.index');

    expect($user->fresh()->current_team_id)->toBe($team->id);
});

it('swaps the old team for the new one on a team page', function (string $route, string $parameter): void {
    $user = User::factory()->create();
    $old = $user->personalTeam();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->fromRoute($route, [$parameter => $old->slug])
        ->post(route('teams.switch', $team))
        ->assertRedirectToRoute($route, [$parameter => $team->slug]);
})->with([
    'dashboard' => ['team.dashboard', 'current_team'],
    'team settings' => ['teams.edit', 'team'],
]);

it('keeps another team in the url and the query string', function (): void {
    $user = User::factory()->create();
    $other = Team::factory()->create();
    $other->members()->attach($user, ['role' => TeamRole::Member->value]);
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->from(route('teams.edit', $other).'?tab=members')
        ->post(route('teams.switch', $team))
        ->assertRedirect(route('teams.edit', ['team' => $other->slug, 'tab' => 'members']));
});

it('stays on a page without a team', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->post(route('teams.switch', $team))
        ->assertRedirectToRoute('user-profile.edit');
});

it('only swaps route parameters that hold the old team', function (): void {
    $user = User::factory()->create();
    $old = Team::factory()->create(['slug' => 'profile']);
    $old->members()->attach($user, ['role' => TeamRole::Member->value]);
    $user->forceFill(['current_team_id' => $old->id])->save();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->post(route('teams.switch', $team))
        ->assertRedirectToRoute('user-profile.edit');
});

it('falls back to the dashboard without a previous page', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->post(route('teams.switch', $team))
        ->assertRedirectToRoute('team.dashboard', ['current_team' => $team->slug]);
});

it('falls back to the dashboard for a previous page on another host', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->from("https://example.org/{$user->personalTeam()?->slug}/dashboard")
        ->post(route('teams.switch', $team))
        ->assertRedirectToRoute('team.dashboard', ['current_team' => $team->slug]);
});

it('falls back to the dashboard for a previous page without a route', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->from(url('missing/page'))
        ->post(route('teams.switch', $team))
        ->assertRedirectToRoute('team.dashboard', ['current_team' => $team->slug]);
});

it('forbids switching to a team the user does not belong to', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $this->actingAs($user)
        ->fromRoute('teams.index')
        ->post(route('teams.switch', $team))
        ->assertNotFound();

    expect($user->fresh()->current_team_id)->not->toBe($team->id);
});
