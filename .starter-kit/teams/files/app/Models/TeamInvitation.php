<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Carbon\CarbonInterface;
use Database\Factories\TeamInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $code
 * @property string $team_id
 * @property string $email
 * @property TeamRole $role
 * @property string $invited_by
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $accepted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Team $team
 * @property-read User $inviter
 */
#[RouteKey('code')]
final class TeamInvitation extends Model
{
    /**
     * @use HasFactory<TeamInvitationFactory>
     */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'code' => 'string',
            'team_id' => 'string',
            'email' => 'string',
            'role' => TeamRole::class,
            'invited_by' => 'string',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    /**
     * @param  Builder<TeamInvitation>  $query
     */
    #[Scope]
    public function joinable(Builder $query): void
    {
        $query->whereHas('team', fn (Builder $team): Builder => $team->where('is_personal', false));
    }

    /**
     * @param  Builder<TeamInvitation>  $query
     */
    #[Scope]
    public function pending(Builder $query): void
    {
        $query->whereNull('accepted_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * @param  Builder<TeamInvitation>  $query
     */
    #[Scope]
    public function pendingFor(Builder $query, User $user): void
    {
        $this->pending($query);
        $this->joinable($query);

        $query->whereRaw('LOWER(email) = ?', [mb_strtolower($user->email)])
            ->with(['team', 'inviter'])
            ->latest();
    }
}
