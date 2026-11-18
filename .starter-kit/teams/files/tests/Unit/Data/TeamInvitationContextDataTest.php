<?php

declare(strict_types=1);

use App\Data\TeamInvitationContextData;
use App\Models\Team;
use App\Models\TeamInvitation;

it('can be created from an invitation', function (): void {
    $invitation = TeamInvitation::factory()->create([
        'code' => 'abc',
        'team_id' => Team::factory()->create(['name' => 'Acme']),
    ]);

    expect(TeamInvitationContextData::fromInvitation($invitation)->toArray())->toBe([
        'code' => 'abc',
        'team_name' => 'Acme',
    ]);
});
