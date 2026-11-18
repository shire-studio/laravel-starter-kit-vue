<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DeleteTeamRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $team = $this->route('team');

        assert($team instanceof Team);

        return [
            'name' => ['required', 'string', Rule::in([$team->name])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.in' => __('The team name does not match.'),
        ];
    }
}
