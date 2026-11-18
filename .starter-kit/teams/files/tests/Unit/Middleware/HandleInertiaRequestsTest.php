<?php

declare(strict_types=1);

use App\Data\TeamData;
use App\Data\UserData;
use App\Enums\TeamRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

it('shares app name from config', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('name')
        ->and($shared['name'])->toBe(config('app.name'));
});

it('shares null user when guest', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('auth')
        ->and($shared['auth'])->toHaveKey('user')
        ->and($shared['auth']['user'])->toBeNull();
});

it('shares authenticated user data', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['auth']['user'])->not->toBeNull()
        ->and($shared['auth']['user'])->toBeInstanceOf(UserData::class)
        ->and($shared['auth']['user']->name)->toBe('Test User')
        ->and($shared['auth']['user']->email)->toBe('test@example.com');
});

it('defaults sidebarOpen to true when no cookie', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('sidebarOpen')
        ->and($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to true when cookie is true', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'true');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to false when cookie is false', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'false');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeFalse();
});

it('includes parent shared data', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('errors');
});

it('shares null current team and no teams for guests', function (): void {
    $shared = new HandleInertiaRequests()->share(Request::create('/', 'GET'));

    expect(value($shared['currentTeam']))->toBeNull()
        ->and(value($shared['teams']))->toBe([]);
});

it('shares the current team and teams for a signed in user', function (): void {
    $user = User::factory()->create(['name' => 'Zed']);
    $team = Team::factory()->create(['name' => 'Acme']);
    $team->members()->attach($user, ['role' => 'member']);

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = new HandleInertiaRequests()->share($request);

    expect(value($shared['currentTeam']))->toBeInstanceOf(TeamData::class)
        ->and(value($shared['currentTeam'])->slug)->toBe($user->personalTeam()?->slug)
        ->and(value($shared['teams']))->toHaveCount(2)
        ->and(value($shared['teams'])[0]->name)->toBe('Acme')
        ->and(value($shared['teams'])[0]->role)->toBe(TeamRole::Member);
});

it('shares null current team when the user has no team', function (): void {
    $user = User::factory()->create();
    $user->teamMemberships()->delete();
    $user->update(['current_team_id' => null]);

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user->refresh());

    expect(value(new HandleInertiaRequests()->share($request)['currentTeam']))->toBeNull();
});

it('loads the teams prop in a constant number of queries', function (): void {
    $user = User::factory()->create();
    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $count = function () use ($request): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        value(new HandleInertiaRequests()->share($request)['teams']);

        return count(DB::getQueryLog());
    };

    $before = $count();

    Team::factory()->count(4)->create()->each(
        fn (Team $team) => $team->members()->attach($user, ['role' => 'member']),
    );

    expect($count())->toBe($before);
});
