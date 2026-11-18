<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TeamInvitation;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class ValidTeamInvitation implements ValidationRule
{
    public function __construct(private User $user, private bool $allowPersonalTeam = false) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $valid = $this->user->hasVerifiedEmail()
            && $value instanceof TeamInvitation
            && $value->isPending()
            && ($this->allowPersonalTeam || ! $value->team->is_personal)
            && mb_strtolower($value->email) === mb_strtolower($this->user->email);

        if (! $valid) {
            $fail(__('This invitation is invalid or has expired.'));
        }
    }
}
