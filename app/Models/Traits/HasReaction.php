<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait HasReaction
{
    /**
     * @return MorphMany<Reaction>
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'model');
    }

    /**
     * @param  User|int  $user
     * @return string|null
     */
    public function getUserReaction(User|int $user): ?string
    {
        /** @var Reaction|null $reaction */
        $reaction = $this->reactions()
            ->where('user_id', $user instanceof User ? $user->id : $user)
            ->first();

        return $reaction?->getName();
    }

    /**
     * @return Collection<string, int>
     */
    public function getAllReactions(): Collection
    {
        return $this->reactions()
            ->select('reaction', DB::raw('count(*)'))
            ->groupBy('reaction')
            ->get()->pluck('count(*)', 'reaction')->sortKeys()
            ->mapWithKeys(fn (int $count, int $reaction) => [Reaction::getReaction($reaction) => $count]);
    }

    /**
     * @param  User|int  $user
     * @param  int|string  $reaction
     * @return void
     */
    public function setReaction(User|int $user, int|string $reaction): void
    {
        if (! is_int($reaction)) {
            $reaction = array_search($reaction, Reaction::REACTIONS);
        }

        if (is_int($reaction) && $reaction >= 0 && $reaction < count(Reaction::REACTIONS)) {
            $this->reactions()->updateOrCreate([
                'user_id' => $user instanceof User ? $user->id : $user,
            ], [
                'reaction' => $reaction,
            ])->save();
        }
    }

    /**
     * @param  User|int  $user
     * @return void
     */
    public function removeReaction(User|int $user): void
    {
        $this->reactions()
            ->where('user_id', $user instanceof User ? $user->id : $user)
            ->delete();
    }
}
