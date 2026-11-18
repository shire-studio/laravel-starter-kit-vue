<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\TeamName;
use Illuminate\Foundation\Http\FormRequest;

final class SaveTeamRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new TeamName],
        ];
    }
}
