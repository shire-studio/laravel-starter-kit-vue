<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Spatie\LaravelData\Data;

final class TeamData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly bool $is_personal,
        public readonly ?TeamRole $role,
        public readonly ?string $role_label,
        public readonly bool $is_current,
        public readonly bool $can_leave,
        public readonly bool $can_update,
    ) {}

    public static function fromTeam(Team $team, User $viewer): self
    {
        $team = $viewer->teamWithMembership($team);
        $role = $viewer->teamRole($team);

        return new self(
            id: $team->id,
            name: $team->name,
            slug: $team->slug,
            is_personal: $team->is_personal,
            role: $role,
            role_label: $role?->label(),
            is_current: $viewer->isCurrentTeam($team),
            can_leave: $viewer->can('leave', $team),
            can_update: $viewer->can('update', $team),
        );
    }
}
