<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUser
{
    public function __construct(private DeleteUserTeams $deleteUserTeams, private DeleteUserPasskeys $deleteUserPasskeys) {}

    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->deleteUserTeams->handle($user);

            $this->deleteUserPasskeys->handle($user);

            $user->delete();
        });
    }
}
