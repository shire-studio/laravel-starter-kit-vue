<?php

declare(strict_types=1);

use App\Actions\DeclineTeamInvitation;
use App\Models\TeamInvitation;

it('declines the invitation', function (): void {
    $invitation = TeamInvitation::factory()->create();

    resolve(DeclineTeamInvitation::class)->handle($invitation);

    expect(TeamInvitation::query()->count())->toBe(0);
});
