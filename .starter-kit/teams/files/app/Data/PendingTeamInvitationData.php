<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\TeamInvitation;
use Spatie\LaravelData\Data;

final class PendingTeamInvitationData extends Data
{
    public function __construct(
        public readonly string $code,
        public readonly string $inviter_name,
        public readonly string $team_name,
        public readonly string $team_slug,
    ) {}

    public static function fromInvitation(TeamInvitation $invitation): self
    {
        return new self(
            code: $invitation->code,
            inviter_name: $invitation->inviter->name,
            team_name: $invitation->team->name,
            team_slug: $invitation->team->slug,
        );
    }
}
