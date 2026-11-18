<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TeamRole;
use Spatie\LaravelData\Data;

final class TeamRoleData extends Data
{
    public function __construct(
        public readonly TeamRole $value,
        public readonly string $label,
    ) {}

    public static function fromRole(TeamRole $role): self
    {
        return new self(
            value: $role,
            label: $role->label(),
        );
    }
}
