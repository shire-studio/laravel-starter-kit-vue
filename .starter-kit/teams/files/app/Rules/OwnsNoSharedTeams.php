<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class OwnsNoSharedTeams implements ValidationRule
{
    public function __construct(private User $user) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ownsSharedTeam = $this->user->ownedTeams()
            ->where('teams.is_personal', false)
            ->whereHas('memberships', fn (Builder $query): Builder => $query->where('user_id', '!=', $this->user->id))
            ->exists();

        if ($ownsSharedTeam) {
            $fail(__('Remove the other members or delete the teams you own before deleting your account.'));
        }
    }
}
