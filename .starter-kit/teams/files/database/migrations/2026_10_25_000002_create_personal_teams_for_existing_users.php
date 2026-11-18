<?php

declare(strict_types=1);

use App\Actions\CreateTeam;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $createTeam = resolve(CreateTeam::class);

        User::query()
            ->whereDoesntHave('teamMemberships')
            ->chunkById(100, function ($users) use ($createTeam): void {
                foreach ($users as $user) {
                    $createTeam->handle($user, ['name' => $user->personalTeamName()], personal: true);
                }
            });
    }
};
