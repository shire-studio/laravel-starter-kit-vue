<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Rules\ValidTeamInvitation;
use Illuminate\Support\Facades\Validator;

function validInvitationValidator(User $user, mixed $invitation): Illuminate\Validation\Validator
{
    return Validator::make(['invitation' => $invitation], ['invitation' => [new ValidTeamInvitation($user)]]);
}

it("accepts a pending invitation for the user's email", function (): void {
    $user = User::factory()->create(['email' => 'frodo@shire.test']);
    $invitation = TeamInvitation::factory()->create(['email' => 'FRODO@shire.test']);

    expect(validInvitationValidator($user, $invitation)->passes())->toBeTrue();
});

it('rejects an invitation for another email', function (): void {
    $user = User::factory()->create(['email' => 'frodo@shire.test']);
    $invitation = TeamInvitation::factory()->create(['email' => 'sam@shire.test']);

    $validator = validInvitationValidator($user, $invitation);

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('invitation'))->toBe('This invitation is invalid or has expired.');
});

it('rejects an invitation for a user without a verified email', function (): void {
    $user = User::factory()->unverified()->create();
    $invitation = TeamInvitation::factory()->create(['email' => $user->email]);

    $validator = validInvitationValidator($user, $invitation);

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('invitation'))->toBe('This invitation is invalid or has expired.');
});

it('rejects an expired invitation', function (): void {
    $user = User::factory()->create();
    $invitation = TeamInvitation::factory()->expired()->create(['email' => $user->email]);

    expect(validInvitationValidator($user, $invitation)->passes())->toBeFalse();
});

it('rejects an accepted invitation', function (): void {
    $user = User::factory()->create();
    $invitation = TeamInvitation::factory()->accepted()->create(['email' => $user->email]);

    expect(validInvitationValidator($user, $invitation)->passes())->toBeFalse();
});

it('rejects values that are not invitations', function (): void {
    expect(validInvitationValidator(User::factory()->create(), 'nope')->passes())->toBeFalse();
});

it('rejects an invitation to a personal team', function (): void {
    $user = User::factory()->create();
    $invitation = TeamInvitation::factory()->create([
        'team_id' => Team::factory()->personal()->create()->id,
        'email' => $user->email,
    ]);

    $validator = validInvitationValidator($user, $invitation);

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('invitation'))->toBe('This invitation is invalid or has expired.');
});

it('accepts an invitation to a personal team when personal teams are allowed', function (): void {
    $user = User::factory()->create();
    $invitation = TeamInvitation::factory()->create([
        'team_id' => Team::factory()->personal()->create()->id,
        'email' => $user->email,
    ]);

    $validator = Validator::make(['invitation' => $invitation], ['invitation' => [new ValidTeamInvitation($user, allowPersonalTeam: true)]]);

    expect($validator->passes())->toBeTrue();
});
