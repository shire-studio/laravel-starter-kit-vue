<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

trait HasTeams
{
    /**
     * @return BelongsToMany<Team, $this, Membership, 'pivot'>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, Membership::class, 'user_id', 'team_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * @return HasManyThrough<Team, Membership, $this>
     */
    public function ownedTeams(): HasManyThrough
    {
        return $this->hasManyThrough(
            Team::class,
            Membership::class,
            'user_id',
            'id',
            'id',
            'team_id',
        )->where('team_members.role', TeamRole::Owner->value);
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function teamMemberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function personalTeamName(): string
    {
        $suffix = "'s Team";

        return mb_rtrim(mb_substr($this->name, 0, 255 - mb_strlen($suffix))).$suffix;
    }

    public function personalTeam(): ?Team
    {
        return $this->ownedTeams()
            ->where('teams.is_personal', true)
            ->first();
    }

    public function currentTeamOrPersonal(): ?Team
    {
        $team = $this->currentTeam ?? null;

        return $team instanceof Team && $this->belongsToTeam($team) ? $team : $this->personalTeam();
    }

    public function teamWithMembership(Team $team): Team
    {
        if ($this->loadedMembership($team) instanceof Membership) {
            return $team;
        }

        return $this->teams()->whereKey($team->id)->first() ?? $team;
    }

    public function belongsToTeam(Team $team): bool
    {
        return $this->loadedMembership($team) instanceof Membership
            || $this->teams()->where('teams.id', $team->id)->exists();
    }

    public function isCurrentTeam(Team $team): bool
    {
        return $this->current_team_id === $team->id;
    }

    public function ownsTeam(Team $team): bool
    {
        return $this->teamRole($team) === TeamRole::Owner;
    }

    public function teamRole(Team $team): ?TeamRole
    {
        $membership = $this->loadedMembership($team)
            ?? $this->teamMemberships()->where('team_id', $team->id)->first();

        return $membership?->role;
    }

    public function hasTeamPermission(Team $team, TeamPermission $permission): bool
    {
        return $this->teamRole($team)?->hasPermission($permission) ?? false;
    }

    private function loadedMembership(Team $team): ?Membership
    {
        foreach ([$team, $this] as $model) {
            $pivot = $model->relationLoaded('pivot') ? $model->getRelation('pivot') : null;

            if ($pivot instanceof Membership && $pivot->team_id === $team->id && $pivot->user_id === $this->id) {
                return $pivot;
            }
        }

        return null;
    }
}
