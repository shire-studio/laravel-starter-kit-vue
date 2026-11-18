<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Actions\CreateTeamInvitationUrl;
use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

#[DeleteWhenMissingModels]
final class TeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TeamInvitation $invitation) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $team = $this->invitation->team->name;

        return (new MailMessage)
            ->subject("You've been invited to join {$team}")
            ->line("{$this->invitation->inviter->name} has invited you to join the {$team} team.")
            ->line('Log in and visit your dashboard to accept or decline this invitation.')
            ->action('Log in', resolve(CreateTeamInvitationUrl::class)->handle($this->invitation));
    }
}
