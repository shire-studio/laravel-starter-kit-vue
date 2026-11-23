<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final readonly class CreateTeamInvitation
{
    /**
     * @param  array{email: string, role: string}  $attributes
     */
    public function handle(Team $team, User $inviter, array $attributes): TeamInvitation
    {
        $email = $attributes['email'];

        $invitation = $team->invitations()->create([
            'code' => Str::random(64),
            'email' => $email,
            'role' => TeamRole::from($attributes['role']),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $email)
            ->notify(new TeamInvitationNotification($invitation));

        return $invitation;
    }
}
