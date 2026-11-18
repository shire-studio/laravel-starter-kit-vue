<?php

declare(strict_types=1);

use App\Actions\CreateTeamInvitation;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;

it('creates an invitation that expires in three days', function (): void {
    Notification::fake();
    $team = Team::factory()->create();
    $inviter = User::factory()->create();

    $invitation = resolve(CreateTeamInvitation::class)->handle($team, $inviter, [
        'email' => 'new@example.com',
        'role' => 'admin',
    ])->refresh();

    expect($invitation->email)->toBe('new@example.com')
        ->and($invitation->role)->toBe(TeamRole::Admin)
        ->and($invitation->invited_by)->toBe($inviter->id)
        ->and($invitation->team_id)->toBe($team->id)
        ->and($invitation->code)->toHaveLength(64)
        ->and($invitation->expires_at?->toDateTimeString())->toBe(now()->addDays(3)->toDateTimeString());
});

it('notifies the invited email', function (): void {
    Notification::fake();

    $invitation = resolve(CreateTeamInvitation::class)->handle(
        Team::factory()->create(),
        User::factory()->create(),
        ['email' => 'new@example.com', 'role' => 'member'],
    );

    Notification::assertSentOnDemand(
        TeamInvitationNotification::class,
        fn (TeamInvitationNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'new@example.com'
            && $notification->invitation->is($invitation),
    );
});
