<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Rules\UniqueTeamInvitation;
use Illuminate\Support\Facades\Validator;

function uniqueInvitationValidator(Team $team, mixed $email): Illuminate\Validation\Validator
{
    return Validator::make(['email' => $email], ['email' => [new UniqueTeamInvitation($team)]]);
}

it('rejects an email with a pending invitation', function (): void {
    $team = Team::factory()->create();
    TeamInvitation::factory()->for($team)->create(['email' => 'bilbo@shire.test']);

    $validator = uniqueInvitationValidator($team, 'Bilbo@Shire.test');

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('email'))->toBe('This user has already been invited to the team.');
});

it('rejects an email of an existing member', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->withoutPersonalTeam()->create(['email' => 'frodo@shire.test']);
    $team->members()->attach($user, ['role' => 'member']);

    $validator = uniqueInvitationValidator($team, 'FRODO@shire.test');

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('email'))->toBe('This user is already a member of the team.');
});

it('accepts an email whose invitation expired', function (): void {
    $team = Team::factory()->create();
    TeamInvitation::factory()->for($team)->expired()->create(['email' => 'sam@shire.test']);

    expect(uniqueInvitationValidator($team, 'sam@shire.test')->passes())->toBeTrue();
});

it('accepts an email invited to another team', function (): void {
    $team = Team::factory()->create();
    TeamInvitation::factory()->create(['email' => 'sam@shire.test']);

    expect(uniqueInvitationValidator($team, 'sam@shire.test')->passes())->toBeTrue();
});

it('ignores values that are not strings', function (): void {
    expect(uniqueInvitationValidator(Team::factory()->create(), null)->passes())->toBeTrue();
});
