<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Team;
use App\Models\User;
use Spatie\LaravelData\Data;

final class TeamPermissionsData extends Data
{
    public function __construct(
        public readonly bool $can_update_team,
        public readonly bool $can_delete_team,
        public readonly bool $can_add_member,
        public readonly bool $can_update_member,
        public readonly bool $can_remove_member,
        public readonly bool $can_create_invitation,
        public readonly bool $can_cancel_invitation,
    ) {}

    public static function fromTeam(Team $team, User $viewer): self
    {
        $team = $viewer->teamWithMembership($team);

        return new self(
            can_update_team: $viewer->can('update', $team),
            can_delete_team: $viewer->can('delete', $team),
            can_add_member: $viewer->can('addMember', $team),
            can_update_member: $viewer->can('updateMember', $team),
            can_remove_member: $viewer->can('removeMember', $team),
            can_create_invitation: $viewer->can('inviteMember', $team),
            can_cancel_invitation: $viewer->can('cancelInvitation', $team),
        );
    }
}
