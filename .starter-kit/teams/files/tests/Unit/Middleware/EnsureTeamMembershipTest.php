<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Http\Middleware\EnsureTeamMembership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function teamWithMember(User $user, TeamRole $role): Team
{
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);

    return $team;
}

function runMembership(?User $user, string $parameter, ?Team $team): Response
{
    $request = Request::create('/probe');
    $route = new Route('GET', "{{$parameter}}/probe", []);
    $route->bind($request);

    if ($team instanceof Team) {
        $route->setParameter($parameter, $team);
    }

    $request->setRouteResolver(fn (): Route => $route);
    $request->setUserResolver(fn (): ?User => $user);

    return resolve(EnsureTeamMembership::class)->handle(
        $request,
        fn (): Response => response('ok'),
    );
}

it('rejects non members', function (): void {
    runMembership(User::factory()->create(), 'current_team', Team::factory()->create());
})->throws(NotFoundHttpException::class);

it('rejects guests', function (): void {
    runMembership(null, 'current_team', Team::factory()->create());
})->throws(NotFoundHttpException::class);

it('rejects routes without a team', function (): void {
    runMembership(User::factory()->create(), 'current_team', null);
})->throws(NotFoundHttpException::class);

it('switches to the team in the url', function (): void {
    $user = User::factory()->create();
    $team = teamWithMember($user, TeamRole::Member);

    runMembership($user, 'current_team', $team);

    expect($user->refresh()->current_team_id)->toBe($team->id);
});

it('leaves the current team alone when it matches', function (): void {
    $user = User::factory()->create();
    $team = teamWithMember($user, TeamRole::Member);
    $user->forceFill(['current_team_id' => $team->id])->save();

    $saves = 0;
    User::saving(function () use (&$saves): void {
        $saves++;
    });

    runMembership($user, 'current_team', $team);

    expect($saves)->toBe(0);
});

it('does not switch teams for team routes', function (): void {
    $user = User::factory()->create();
    $before = $user->current_team_id;

    runMembership($user, 'team', teamWithMember($user, TeamRole::Admin));

    expect($user->refresh()->current_team_id)->toBe($before);
});

it('refreshes the url defaults after switching teams', function (): void {
    $user = User::factory()->create();
    $team = teamWithMember($user, TeamRole::Member);
    URL::defaults(['current_team' => 'old-slug', 'team' => 'old-slug']);

    runMembership($user, 'current_team', $team);

    expect(URL::getDefaultParameters())->toMatchArray(['current_team' => $team->slug, 'team' => $team->slug]);
});

it('resolves slug bound routes without parameters after switching teams', function (): void {
    $router = resolve(Router::class);
    $router->get('{current_team:slug}/switched', fn (): string => 'ok')->name('switched.current');
    $router->get('switched/{team:slug}', fn (): string => 'ok')->name('switched.team');
    $router->getRoutes()->refreshNameLookups();
    $user = User::factory()->create();
    $team = teamWithMember($user, TeamRole::Member);
    URL::defaults(['current_team' => 'old', 'current_team:slug' => 'old', 'team' => 'old', 'team:slug' => 'old']);

    runMembership($user, 'current_team', $team);

    expect(route('switched.current'))->toBe(url("/{$team->slug}/switched"))
        ->and(route('switched.team'))->toBe(url("/switched/{$team->slug}"));
});
