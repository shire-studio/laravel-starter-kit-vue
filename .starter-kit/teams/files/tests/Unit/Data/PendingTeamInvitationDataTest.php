<?php

declare(strict_types=1);

use App\Data\PendingTeamInvitationData;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

it('can be created from an invitation', function (): void {
    $invitation = TeamInvitation::factory()->create([
        'code' => 'abc',
        'team_id' => Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']),
        'invited_by' => User::factory()->create(['name' => 'Ivy']),
    ]);

    expect(PendingTeamInvitationData::fromInvitation($invitation)->toArray())->toBe([
        'code' => 'abc',
        'inviter_name' => 'Ivy',
        'team_name' => 'Acme',
        'team_slug' => 'acme',
    ]);
});
