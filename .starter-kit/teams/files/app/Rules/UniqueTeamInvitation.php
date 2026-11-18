<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Team;
use App\Models\TeamInvitation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class UniqueTeamInvitation implements ValidationRule
{
    public function __construct(private Team $team) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = mb_strtolower(is_string($value) ? $value : '');

        $isMember = $this->team->members()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        if ($isMember) {
            $fail(__('This user is already a member of the team.'));

            return;
        }

        $invitations = TeamInvitation::query()
            ->where('team_id', $this->team->id)
            ->whereRaw('LOWER(email) = ?', [$email]);

        $invitations->scopes('pending');

        $hasPendingInvitation = $invitations->exists();

        if ($hasPendingInvitation) {
            $fail(__('This user has already been invited to the team.'));
        }
    }
}
