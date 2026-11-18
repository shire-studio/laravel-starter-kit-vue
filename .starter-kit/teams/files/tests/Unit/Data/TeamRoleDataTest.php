<?php

declare(strict_types=1);

use App\Data\TeamRoleData;
use App\Enums\TeamRole;

it('can be created from a role', function (): void {
    $data = TeamRoleData::fromRole(TeamRole::Admin);

    expect($data->value)->toBe(TeamRole::Admin)
        ->and($data->toArray())->toBe([
            'value' => 'admin',
            'label' => 'Admin',
        ]);
});
