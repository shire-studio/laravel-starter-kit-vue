<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\TeamData;
use App\Data\UserData;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name', 'Laravel'),
            'auth' => [
                'user' => $user instanceof User ? UserData::from($user) : null,
            ],
            'currentTeam' => fn (): ?TeamData => $user instanceof User && ($team = $user->currentTeamOrPersonal()) instanceof Team
                ? TeamData::fromTeam($team, $user)
                : null,
            'teams' => fn (): array => $user instanceof User
                ? $user->teams()->orderByRaw('LOWER(teams.name)')->get()
                    ->map(fn (Team $team): TeamData => TeamData::fromTeam($team, $user))
                    ->all()
                : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
