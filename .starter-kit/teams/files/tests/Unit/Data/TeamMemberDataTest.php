<?php

declare(strict_types=1);

use App\Data\TeamMemberData;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('can be created from a member, team and viewer', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create(['name' => 'Jo', 'email' => 'jo@example.com']);
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    expect(TeamMemberData::fromMember($member, $team, $owner)->toArray())->toBe([
        'id' => $member->id,
        'name' => 'Jo',
        'email' => 'jo@example.com',
        'role' => 'member',
        'role_label' => 'Member',
        'can_update' => true,
        'can_remove' => true,
    ]);
});

it('takes what the viewer may do to a member from the team policy', function (TeamRole $viewerRole, TeamRole $memberRole, bool $canUpdate, bool $canRemove): void {
    $viewer = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($viewer, ['role' => $viewerRole->value]);
    $team->members()->attach($member, ['role' => $memberRole->value]);

    $data = TeamMemberData::fromMember($member, $team, $viewer);

    expect($data->role)->toBe($memberRole)
        ->and($data->can_update)->toBe($canUpdate)
        ->and($data->can_remove)->toBe($canRemove);
})->with([
    'owner viewing an admin' => [TeamRole::Owner, TeamRole::Admin, true, true],
    'owner viewing an owner' => [TeamRole::Owner, TeamRole::Owner, false, false],
    'admin viewing a member' => [TeamRole::Admin, TeamRole::Member, false, false],
    'member viewing a member' => [TeamRole::Member, TeamRole::Member, false, false],
]);

it('reads members listed through the team without a query', function (): void {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach(User::factory()->count(3)->create(), ['role' => TeamRole::Member->value]);
    $team = $owner->teamWithMembership($team);
    $members = $team->members()->get();

    DB::enableQueryLog();

    $members->each(fn (User $member): TeamMemberData => TeamMemberData::fromMember($member, $team, $owner));

    expect(DB::getQueryLog())->toBeEmpty();
});
