<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

final readonly class CreateUser
{
    public function __construct(private CreateTeam $createTeam) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password): User
    {
        return DB::transaction(function () use ($attributes, $password): User {
            $user = User::query()->create([
                ...$attributes,
                'password' => Hash::make($password),
            ]);

            $this->createTeam->handle($user, ['name' => $user->personalTeamName()], personal: true);

            return $user;
        });
    }
}
