<?php

declare(strict_types=1);

use App\Data\TeamInvitationData;
use App\Enums\TeamRole;
use App\Models\TeamInvitation;

it('can be created from an invitation', function (): void {
    $invitation = TeamInvitation::factory()->create([
        'code' => 'abc',
        'email' => 'jo@example.com',
        'role' => TeamRole::Admin,
    ]);

    expect(TeamInvitationData::fromInvitation($invitation)->toArray())->toBe([
        'code' => 'abc',
        'email' => 'jo@example.com',
        'role' => 'admin',
        'role_label' => 'Admin',
        'created_at' => $invitation->created_at->toIso8601String(),
    ]);
});
