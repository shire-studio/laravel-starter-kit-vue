<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;

final readonly class UpdateTeam
{
    public function __construct(private CreateTeamSlug $createTeamSlug) {}

    /**
     * @param  array{name: string}  $attributes
     */
    public function handle(Team $team, array $attributes): Team
    {
        $name = $attributes['name'];

        $team->update([
            'name' => $name,
            'slug' => $name === $team->name ? $team->slug : $this->createTeamSlug->handle($name, $team),
        ]);

        return $team;
    }
}
