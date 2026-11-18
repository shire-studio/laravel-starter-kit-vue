<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Spatie\LaravelData\Data;

final class TeamMemberData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly TeamRole $role,
        public readonly string $role_label,
        public readonly bool $can_update,
        public readonly bool $can_remove,
    ) {}

    public static function fromMember(User $member, Team $team, User $viewer): self
    {
        $role = $member->teamRole($team) ?? TeamRole::Member;
        $canManage = $viewer->can('manageMember', [$team, $member]);

        return new self(
            id: $member->id,
            name: $member->name,
            email: $member->email,
            role: $role,
            role_label: $role->label(),
            can_update: $canManage && $viewer->can('updateMember', $team),
            can_remove: $canManage && $viewer->can('removeMember', $team),
        );
    }
}
