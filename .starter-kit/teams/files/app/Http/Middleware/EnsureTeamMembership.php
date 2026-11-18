<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\SwitchTeam;
use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureTeamMembership
{
    public function __construct(private SwitchTeam $switchTeam) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $team = $request->route('current_team') ?? $request->route('team');

        abort_unless($user instanceof User && $team instanceof Team && $user->belongsToTeam($team), 404);

        if ($request->route('current_team') instanceof Team && ! $user->isCurrentTeam($team)) {
            $this->switchTeam->handle($user, $team);
            SetTeamUrlDefaults::forTeam($team);
        }

        return $next($request);
    }
}
