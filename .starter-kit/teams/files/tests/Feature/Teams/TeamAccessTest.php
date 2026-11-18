<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

it('hides team settings from non members', function (string $method, string $route, array $body): void {
    $team = Team::factory()->create(['name' => 'Acme']);
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $invitation = TeamInvitation::factory()->create(['team_id' => $team->id]);

    $parameters = match ($route) {
        'teams.members.update', 'teams.members.destroy' => [$team, $member],
        'teams.invitations.destroy' => [$team, $invitation],
        default => [$team],
    };

    $this->actingAs(User::factory()->create())
        ->call($method, route($route, $parameters), $body)
        ->assertNotFound();
})->with([
    'edit' => ['GET', 'teams.edit', []],
    'update' => ['PATCH', 'teams.update', ['name' => '']],
    'destroy' => ['DELETE', 'teams.destroy', ['name' => 'Acme']],
    'switch' => ['POST', 'teams.switch', []],
    'leave' => ['DELETE', 'teams.leave', []],
    'member update' => ['PATCH', 'teams.members.update', ['role' => 'owner']],
    'member destroy' => ['DELETE', 'teams.members.destroy', []],
    'invitation store' => ['POST', 'teams.invitations.store', ['email' => 'bad', 'role' => 'owner']],
    'invitation destroy' => ['DELETE', 'teams.invitations.destroy', []],
]);

it('returns 404 not a validation error when a member without the permission probes invitations', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $team->members()->attach($other, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->post(route('teams.invitations.store', $team), ['email' => $other->email, 'role' => 'member'])
        ->assertNotFound();
});

it('requires a verified email', function (string $method, string $route): void {
    $user = User::factory()->unverified()->create();
    $team = Team::factory()->create(['name' => 'Acme']);
    $member = User::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $sent = TeamInvitation::factory()->create(['team_id' => $team->id]);
    $received = TeamInvitation::factory()->create(['email' => $user->email]);

    $parameters = match ($route) {
        'teams.index', 'teams.store' => [],
        'teams.members.update', 'teams.members.destroy' => [$team, $member],
        'teams.invitations.destroy' => [$team, $sent],
        'invitations.accept', 'invitations.decline' => [$received],
        default => [$team],
    };

    $this->actingAs($user)
        ->fromRoute('home')
        ->call($method, route($route, $parameters), ['name' => 'Acme', 'email' => 'new@example.com', 'role' => 'member'])
        ->assertRedirectToRoute('verification.notice');

    expect($received->fresh()?->accepted_at)->toBeNull()
        ->and(TeamInvitation::query()->where('email', 'new@example.com')->exists())->toBeFalse();
})->with([
    'teams index' => ['GET', 'teams.index'],
    'teams store' => ['POST', 'teams.store'],
    'teams edit' => ['GET', 'teams.edit'],
    'teams update' => ['PATCH', 'teams.update'],
    'teams destroy' => ['DELETE', 'teams.destroy'],
    'teams switch' => ['POST', 'teams.switch'],
    'teams leave' => ['DELETE', 'teams.leave'],
    'member update' => ['PATCH', 'teams.members.update'],
    'member destroy' => ['DELETE', 'teams.members.destroy'],
    'invitation store' => ['POST', 'teams.invitations.store'],
    'invitation destroy' => ['DELETE', 'teams.invitations.destroy'],
    'invitation accept' => ['POST', 'invitations.accept'],
    'invitation decline' => ['DELETE', 'invitations.decline'],
]);
