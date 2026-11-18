<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [self::Admin, self::Member];
    }

    public static function assignableRule(): Enum
    {
        return Rule::enum(self::class)->except([self::Owner]);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return list<TeamPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => TeamPermission::cases(),
            self::Admin => [
                TeamPermission::UpdateTeam,
                TeamPermission::CreateInvitation,
                TeamPermission::CancelInvitation,
            ],
            self::Member => [],
        };
    }

    public function hasPermission(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
