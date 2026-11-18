<?php

declare(strict_types=1);

use App\Actions\FindTeamInvitationContext;
use App\Models\TeamInvitation;

it('finds a pending invitation by code', function (): void {
    $invitation = TeamInvitation::factory()->create();

    expect(resolve(FindTeamInvitationContext::class)->handle($invitation->code)?->is($invitation))
        ->toBeTrue();
});

it('returns null for missing, empty, unknown, expired and accepted codes', function (): void {
    $expired = TeamInvitation::factory()->expired()->create();
    $accepted = TeamInvitation::factory()->accepted()->create();
    $action = resolve(FindTeamInvitationContext::class);

    expect($action->handle(null))->toBeNull()
        ->and($action->handle(''))->toBeNull()
        ->and($action->handle('unknown'))->toBeNull()
        ->and($action->handle($expired->code))->toBeNull()
        ->and($action->handle($accepted->code))->toBeNull();
});
