<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidTeamInvitation;
use Illuminate\Foundation\Http\FormRequest;

final class DeclineTeamInvitationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return ['invitation' => $this->route('invitation')];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $user = $this->user();

        assert($user instanceof User);

        return [
            'invitation' => [new ValidTeamInvitation($user, allowPersonalTeam: true)],
        ];
    }
}
