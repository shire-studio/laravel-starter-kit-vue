<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;

it('mails the invitation', function (): void {
    $inviter = User::factory()->create(['name' => 'Jane Doe']);
    $team = Team::factory()->create(['name' => 'Acme']);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $inviter->id,
    ]);

    $mail = new TeamInvitationNotification($invitation)->toMail(new stdClass());

    expect($mail->subject)->toBe("You've been invited to join Acme")
        ->and($mail->introLines)->toBe([
            'Jane Doe has invited you to join the Acme team.',
            'Log in and visit your dashboard to accept or decline this invitation.',
        ])
        ->and($mail->actionText)->toBe('Log in')
        ->and($mail->actionUrl)->toBe(route('login', ['invitation' => $invitation->code]));
});

it('is delivered by mail only', function (): void {
    $notification = new TeamInvitationNotification(TeamInvitation::factory()->create());

    expect($notification->via(new stdClass()))->toBe(['mail']);
});

it('is dropped from the queue when its invitation no longer exists', function (): void {
    $job = new SendQueuedNotifications(new AnonymousNotifiable(), new TeamInvitationNotification(TeamInvitation::factory()->create()));

    expect($job->deleteWhenMissingModels)->toBeTrue();
});
