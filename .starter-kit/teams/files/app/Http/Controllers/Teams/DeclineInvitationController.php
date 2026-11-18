<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\DeclineTeamInvitation;
use App\Http\Requests\DeclineTeamInvitationRequest;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class DeclineInvitationController
{
    public function __invoke(TeamInvitation $invitation, DeclineTeamInvitationRequest $request, DeclineTeamInvitation $declineTeamInvitation): RedirectResponse
    {
        $declineTeamInvitation->handle($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation declined.')]);

        return to_route('dashboard');
    }
}
