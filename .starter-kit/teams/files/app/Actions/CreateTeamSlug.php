<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final readonly class CreateTeamSlug
{
    private const int MAX_BASE_LENGTH = 240;

    public function handle(string $name, ?Team $ignoring = null): string
    {
        $slug = mb_rtrim(mb_substr(Str::slug($name), 0, self::MAX_BASE_LENGTH), '-');

        if ($slug === '') {
            $slug = 'team';
        }

        $query = Team::withTrashed()
            ->where(function (Builder $query) use ($slug): void {
                $query->where('slug', $slug)
                    ->orWhere('slug', 'like', $slug.'-%');
            });

        if ($ignoring instanceof Team) {
            $query->where('id', '!=', $ignoring->id);
        }

        $existing = $query->get();

        if ($existing->isEmpty()) {
            return $slug;
        }

        $highest = $existing
            ->map(function (Team $existingTeam) use ($slug): int {
                if ($existingTeam->slug === $slug) {
                    return 0;
                }

                return preg_match('/^'.preg_quote($slug, '/').'-(\d+)$/', $existingTeam->slug, $matches) === 1
                    ? (int) $matches[1]
                    : 0;
            })
            ->max();

        return $slug.'-'.($highest + 1);
    }
}
