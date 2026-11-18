<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\TeamInvitation;

it('shows the inviting team on the login page', function (): void {
    $team = Team::factory()->create(['name' => 'Acme']);
    $invitation = TeamInvitation::factory()->create(['team_id' => $team->id]);

    $this->get(route('login', ['invitation' => $invitation->code]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/Login')
            ->where('teamInvitation.code', $invitation->code)
            ->where('teamInvitation.team_name', 'Acme'));
});

it('shows the inviting team on the register page', function (): void {
    $team = Team::factory()->create(['name' => 'Acme']);
    $invitation = TeamInvitation::factory()->create(['team_id' => $team->id]);

    $this->get(route('register', ['invitation' => $invitation->code]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/Register')
            ->where('teamInvitation.code', $invitation->code)
            ->where('teamInvitation.team_name', 'Acme'));
});

it('ignores an expired invitation code', function (): void {
    $invitation = TeamInvitation::factory()->expired()->create();

    $this->get(route('login', ['invitation' => $invitation->code]))
        ->assertInertia(fn ($page) => $page->where('teamInvitation', null));

    $this->get(route('register', ['invitation' => $invitation->code]))
        ->assertInertia(fn ($page) => $page->where('teamInvitation', null));
});

it('ignores an invitation code for a personal team', function (): void {
    $invitation = TeamInvitation::factory()->create(['team_id' => Team::factory()->personal()->create()->id]);

    $this->get(route('login', ['invitation' => $invitation->code]))
        ->assertInertia(fn ($page) => $page->where('teamInvitation', null));

    $this->get(route('register', ['invitation' => $invitation->code]))
        ->assertInertia(fn ($page) => $page->where('teamInvitation', null));
});

it('ignores an unknown invitation code', function (): void {
    $this->get(route('login', ['invitation' => 'unknown']))
        ->assertInertia(fn ($page) => $page->where('teamInvitation', null));

    $this->get(route('register', ['invitation' => ['array']]))
        ->assertInertia(fn ($page) => $page->where('teamInvitation', null));

    $this->get(route('register'))
        ->assertInertia(fn ($page) => $page->where('teamInvitation', null));
});

it('ignores an accepted invitation code', function (): void {
    $invitation = TeamInvitation::factory()->accepted()->create();

    $this->get(route('login', ['invitation' => $invitation->code]))
        ->assertInertia(fn ($page) => $page->where('teamInvitation', null));

    $this->get(route('register', ['invitation' => $invitation->code]))
        ->assertInertia(fn ($page) => $page->where('teamInvitation', null));
});
