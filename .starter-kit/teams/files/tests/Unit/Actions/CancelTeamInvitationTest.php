<?php

declare(strict_types=1);

use App\Actions\CancelTeamInvitation;
use App\Models\TeamInvitation;

it('cancels the invitation', function (): void {
    $invitation = TeamInvitation::factory()->create();

    resolve(CancelTeamInvitation::class)->handle($invitation);

    expect(TeamInvitation::query()->count())->toBe(0);
});
