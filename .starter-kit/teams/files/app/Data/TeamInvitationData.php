<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TeamRole;
use App\Models\TeamInvitation;
use Spatie\LaravelData\Data;

final class TeamInvitationData extends Data
{
    public function __construct(
        public readonly string $code,
        public readonly string $email,
        public readonly TeamRole $role,
        public readonly string $role_label,
        public readonly string $created_at,
    ) {}

    public static function fromInvitation(TeamInvitation $invitation): self
    {
        return new self(
            code: $invitation->code,
            email: $invitation->email,
            role: $invitation->role,
            role_label: $invitation->role->label(),
            created_at: $invitation->created_at->toIso8601String(),
        );
    }
}
