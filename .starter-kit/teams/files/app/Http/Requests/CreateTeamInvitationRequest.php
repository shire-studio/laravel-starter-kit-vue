<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Rules\UniqueTeamInvitation;
use App\Rules\ValidEmail;
use Illuminate\Foundation\Http\FormRequest;

final class CreateTeamInvitationRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $team = $this->route('team');

        assert($team instanceof Team);

        return [
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'email',
                new ValidEmail,
                new UniqueTeamInvitation($team),
            ],
            'role' => ['required', 'string', TeamRole::assignableRule()],
        ];
    }
}
