<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\OwnsNoSharedTeams;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteUserRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $user = $this->user();

        assert($user instanceof User);

        return [
            'password' => ['required', 'current_password', new OwnsNoSharedTeams($user)],
        ];
    }
}
