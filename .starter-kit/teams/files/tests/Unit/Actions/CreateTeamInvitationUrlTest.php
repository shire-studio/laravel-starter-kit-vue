<?php

declare(strict_types=1);

use App\Actions\CreateTeamInvitationUrl;
use App\Models\TeamInvitation;

it('links to login with the invitation code', function (): void {
    $invitation = TeamInvitation::factory()->create();

    expect(resolve(CreateTeamInvitationUrl::class)->handle($invitation))
        ->toBe(route('login', ['invitation' => $invitation->code]));
});
