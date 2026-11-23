<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetTeamUrlDefaults
{
    public static function forTeam(Team $team): void
    {
        URL::defaults([
            'current_team' => $team->slug,
            'current_team:slug' => $team->slug,
            'team' => $team->slug,
            'team:slug' => $team->slug,
        ]);
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ($team = $user->currentTeamOrPersonal()) instanceof Team) {
            self::forTeam($team);
        }

        return $next($request);
    }
}
