<?php

declare(strict_types=1);

use App\Rules\TeamName;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

function teamNamePasses(mixed $name): bool
{
    return Validator::make(['name' => $name], ['name' => [new TeamName()]])->passes();
}

it('rejects route prefixes', function (string $name): void {
    Route::get('/custom-prefix/{item}', fn (): string => 'ok');

    expect(teamNamePasses($name))->toBeFalse();
})->with(['settings', 'login', 'teams', 'custom-prefix']);

it('rejects reserved names', function (string $name): void {
    expect(teamNamePasses($name))->toBeFalse();
})->with(['admin', '404']);

it('ignores case and whitespace', function (): void {
    expect(teamNamePasses(' Settings '))->toBeFalse();
});

it('rejects names whose slug is reserved', function (string $name): void {
    expect(teamNamePasses($name))->toBeFalse();
})->with(['Settings!', 'Dashboard.', 'Sign In']);

it('accepts other names', function (): void {
    expect(teamNamePasses('Fellowship of the Ring'))->toBeTrue();
});

it('has a full sentence message', function (): void {
    $validator = Validator::make(['name' => 'admin'], ['name' => [new TeamName()]]);

    expect($validator->errors()->first('name'))->toBe('This team name is reserved and cannot be used.');
});

it('ignores values that are not strings', function (): void {
    expect(teamNamePasses(['admin']))->toBeTrue();
});
