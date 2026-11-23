<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\SwitchTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\HttpException;

final readonly class SwitchTeamController
{
    public function __invoke(Request $request, Team $team, #[CurrentUser] User $user, SwitchTeam $switchTeam, Router $router): RedirectResponse
    {
        $previousTeam = $user->currentTeamOrPersonal();

        $switchTeam->handle($user, $team);

        $dashboard = route('team.dashboard', ['current_team' => $team->slug]);
        $previous = Request::create(url()->previous($dashboard));

        if ($previous->getHttpHost() !== $request->getHttpHost()) {
            return redirect()->to($dashboard);
        }

        try {
            $route = $router->getRoutes()->match($previous);
        } catch (HttpException) {
            return redirect()->to($dashboard);
        }

        $parameters = array_map(
            fn (mixed $value): mixed => $value === $previousTeam?->slug ? $team->slug : $value,
            $route->parameters(),
        );

        return redirect()->to(url()->toRoute($route, $parameters + $previous->query(), true));
    }
}
