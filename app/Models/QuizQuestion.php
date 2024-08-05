<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class QuizQuestion
 *
 * @property int $id
 * @property int $quiz_id
 * @property int $type
 * @property string $question
 * @property array<string, int|array<int, int|string>>|null $settings
 * @property int $index
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Quiz $quiz
 * @property Collection<int, QuizAnswer> $quizAnswers
 */
class QuizQuestion extends Model
{
    use Traits\HasMedia;

    public const TYPE_ONE_ANSWER = 0;

    public const TYPE_MULTIPLE_ANSWERS = 1;

    public const TYPE_TEXT = 2;

    public const TYPE_SCALE = 3;

    protected $fillable = ['type', 'question', 'settings'];

    protected $casts = [
        'settings' => 'array',
        'index' => 'int',
    ];

    /**
     * @return BelongsTo<Quiz, QuizQuestion>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return HasMany<QuizAnswer>
     */
    public function quizAnswers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }
}
