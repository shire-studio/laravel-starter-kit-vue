<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;

function teamWithRole(?TeamRole $role, bool $personal = false): array
{
    $team = $personal ? Team::factory()->personal()->create() : Team::factory()->create();
    $user = User::factory()->withoutPersonalTeam()->create();

    if ($role instanceof TeamRole) {
        $team->members()->attach($user, ['role' => $role->value]);
    }

    return [new TeamPolicy(), $user, $team];
}

it('allows only members to view a team', function (): void {
    foreach ([TeamRole::Owner, TeamRole::Admin, TeamRole::Member] as $role) {
        [$policy, $user, $team] = teamWithRole($role);

        expect($policy->view($user, $team))->toBeTrue();
    }

    [$policy, $user, $team] = teamWithRole(null);

    expect($policy->view($user, $team))->toBeFalse();
});

it('allows owners and admins to update a team', function (): void {
    foreach ([[TeamRole::Owner, true], [TeamRole::Admin, true], [TeamRole::Member, false], [null, false]] as [$role, $allowed]) {
        [$policy, $user, $team] = teamWithRole($role);

        expect($policy->update($user, $team))->toBe($allowed);
    }
});

it('allows only owners to delete a team', function (): void {
    foreach ([[TeamRole::Owner, true], [TeamRole::Admin, false], [TeamRole::Member, false], [null, false]] as [$role, $allowed]) {
        [$policy, $user, $team] = teamWithRole($role);

        expect($policy->delete($user, $team))->toBe($allowed);
    }
});

it('forbids deleting a personal team', function (): void {
    [$policy, $user, $team] = teamWithRole(TeamRole::Owner, personal: true);

    expect($policy->delete($user, $team))->toBeFalse();
});

it('allows admins and members to leave a team', function (): void {
    foreach ([[TeamRole::Admin, true], [TeamRole::Member, true], [null, false]] as [$role, $allowed]) {
        [$policy, $user, $team] = teamWithRole($role);

        expect($policy->leave($user, $team))->toBe($allowed);
    }
});

it('forbids owners leaving their team', function (): void {
    [$policy, $user, $team] = teamWithRole(TeamRole::Owner);

    expect($policy->leave($user, $team))->toBeFalse();
});

it('forbids leaving a personal team', function (): void {
    [$policy, $user, $team] = teamWithRole(TeamRole::Member, personal: true);

    expect($policy->leave($user, $team))->toBeFalse();
});

it('allows only owners to add members', function (): void {
    foreach ([[TeamRole::Owner, true], [TeamRole::Admin, false], [TeamRole::Member, false], [null, false]] as [$role, $allowed]) {
        [$policy, $user, $team] = teamWithRole($role);

        expect($policy->addMember($user, $team))->toBe($allowed);
    }
});

it('allows only owners to update members', function (): void {
    foreach ([[TeamRole::Owner, true], [TeamRole::Admin, false], [TeamRole::Member, false], [null, false]] as [$role, $allowed]) {
        [$policy, $user, $team] = teamWithRole($role);

        expect($policy->updateMember($user, $team))->toBe($allowed);
    }
});

it('allows only owners to remove members', function (): void {
    foreach ([[TeamRole::Owner, true], [TeamRole::Admin, false], [TeamRole::Member, false], [null, false]] as [$role, $allowed]) {
        [$policy, $user, $team] = teamWithRole($role);

        expect($policy->removeMember($user, $team))->toBe($allowed);
    }
});

it('allows owners and admins to invite members', function (): void {
    foreach ([[TeamRole::Owner, true], [TeamRole::Admin, true], [TeamRole::Member, false], [null, false]] as [$role, $allowed]) {
        [$policy, $user, $team] = teamWithRole($role);

        expect($policy->inviteMember($user, $team))->toBe($allowed);
    }
});

it('allows owners and admins to cancel invitations', function (): void {
    foreach ([[TeamRole::Owner, true], [TeamRole::Admin, true], [TeamRole::Member, false], [null, false]] as [$role, $allowed]) {
        [$policy, $user, $team] = teamWithRole($role);

        expect($policy->cancelInvitation($user, $team))->toBe($allowed);
    }
});

it('lets owners manage other members', function (): void {
    [$policy, $owner, $team] = teamWithRole(TeamRole::Owner);
    $member = User::factory()->withoutPersonalTeam()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    expect($policy->manageMember($owner, $team, $member))->toBeTrue();
});

it('forbids managing the owner', function (): void {
    [$policy, $owner, $team] = teamWithRole(TeamRole::Owner);

    expect($policy->manageMember($owner, $team, $owner))->toBeFalse();
});

it('forbids managing a non member', function (): void {
    [$policy, $owner, $team] = teamWithRole(TeamRole::Owner);
    $stranger = User::factory()->withoutPersonalTeam()->create();

    expect($policy->manageMember($owner, $team, $stranger))->toBeFalse();
});

it('never lets anyone invite or add members to a personal team', function (): void {
    foreach ([TeamRole::Owner, TeamRole::Admin, TeamRole::Member, null] as $role) {
        [$policy, $user, $team] = teamWithRole($role, personal: true);

        expect($policy->inviteMember($user, $team))->toBeFalse()
            ->and($policy->addMember($user, $team))->toBeFalse();
    }
});

it('still lets an owner cancel an invitation on a personal team', function (): void {
    [$policy, $user, $team] = teamWithRole(TeamRole::Owner, personal: true);

    expect($policy->cancelInvitation($user, $team))->toBeTrue();
});
