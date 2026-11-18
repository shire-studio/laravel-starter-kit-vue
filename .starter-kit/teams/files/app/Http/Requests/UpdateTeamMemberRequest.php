<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TeamRole;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTeamMemberRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', TeamRole::assignableRule()],
        ];
    }
}
