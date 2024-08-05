<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Quiz
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property string $name
 * @property string $description
 * @property Carbon|null $date_start
 * @property Carbon|null $date_end
 * @property bool $is_active
 * @property bool $published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $published_at
 * @property Organization $organization
 * @property Collection<int, QuizQuestion> $quizQuestions
 * @property User $user
 */
class Quiz extends Model
{
    use Traits\HasMedia;

    protected $fillable = ['name', 'description', 'date_start', 'date_end'];

    protected $casts = [
        'is_active' => 'bool',
        'published' => 'bool',
    ];

    protected $dates = ['date_start', 'date_end', 'published_at'];

    /**
     * @return BelongsTo<Organization, Quiz>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<QuizQuestion>
     */
    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)
            ->orderBy('index');
    }

    /**
     * @return HasMany<QuizQuestion>
     */
    public function quizQuestionsAnswered(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)
            ->orderBy('index');
    }

    /**
     * @return BelongsTo<User, Quiz>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Quiz>  $query
     * @param  bool  $isActive
     * @return Builder<Quiz>
     */
    public function scopeActive(Builder $query, bool $isActive = true): Builder
    {
        return $query
            ->where('published', true)
            ->where('is_active', $isActive)
            ->where(static function (Builder|HasMany $query) {
                $query
                    ->whereNull('date_end')
                    ->orWhereDate('date_end', '>', now());
            });
    }

    /**
     * @param  Builder<Quiz>  $query
     * @param  bool  $isClosed
     * @return Builder<Quiz>
     */
    public function scopeClosed(Builder $query, bool $isClosed = true): Builder
    {
        $query->where('published', true);

        if ($isClosed) {
            return $query
                ->whereNotNull('date_end')
                ->whereDate('date_end', '<=', now());
        }

        return $query->where(static function (Builder|HasMany $query) {
            $query
                ->whereNull('date_end')
                ->orWhereDate('date_end', '>', now());
        });
    }

    public function isActive(): bool
    {
        return $this->is_active
            && ! $this->isClosed();
    }

    public function isClosed(): bool
    {
        return $this->is_active
            && $this->date_end !== null
            && $this->date_end->isBefore(now());
    }

    protected static function boot(): void
    {
        parent::boot();

        self::updated(static function (Quiz $quiz) {
            if ($quiz->published && $quiz->published_at === null) {
                $quiz->published_at = $quiz->updated_at;

                if ($quiz->date_start === null || $quiz->date_start->isBefore(now())) {
                    $quiz->is_active = true;
                }

                $quiz->save();
            }
        });
    }
}
