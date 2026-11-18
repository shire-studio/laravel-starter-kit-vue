<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\AcceptTeamInvitation;
use App\Http\Requests\AcceptTeamInvitationRequest;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class AcceptInvitationController
{
    public function __invoke(TeamInvitation $invitation, AcceptTeamInvitationRequest $request, #[CurrentUser] User $user, AcceptTeamInvitation $acceptTeamInvitation): RedirectResponse
    {
        $acceptTeamInvitation->handle($invitation, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation accepted.')]);

        return to_route('dashboard');
    }
}
