<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateTeam
{
    public function __construct(
        private CreateTeamSlug $createTeamSlug,
        private SwitchTeam $switchTeam,
    ) {}

    /**
     * @param  array{name: string}  $attributes
     */
    public function handle(User $user, array $attributes, bool $personal = false): Team
    {
        return DB::transaction(function () use ($user, $attributes, $personal): Team {
            $name = $attributes['name'];

            $team = Team::query()->create([
                'name' => $name,
                'slug' => $this->createTeamSlug->handle($name),
                'is_personal' => $personal,
            ]);

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
            ]);

            $this->switchTeam->handle($user, $team);

            return $team;
        });
    }
}
