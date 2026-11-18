<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\TeamInvitation;
use Spatie\LaravelData\Data;

final class TeamInvitationContextData extends Data
{
    public function __construct(
        public readonly string $code,
        public readonly string $team_name,
    ) {}

    public static function fromInvitation(TeamInvitation $invitation): self
    {
        return new self(
            code: $invitation->code,
            team_name: $invitation->team->name,
        );
    }
}
