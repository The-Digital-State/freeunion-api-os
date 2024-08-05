<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * class QuizAnswer
 *
 * @property int $id
 * @property int $quiz_question_id
 * @property int $user_id
 * @property mixed $answer
 * @property int $points
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property QuizQuestion $quizQuestion
 * @property User $user
 */
class QuizAnswer extends Model
{
    protected $fillable = ['user_id', 'answer', 'points'];

    protected $casts = [
        'points' => 'int',
    ];

    /**
     * @return BelongsTo<QuizQuestion, QuizAnswer>
     */
    public function quizQuestion(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class);
    }

    /**
     * @return BelongsTo<User, QuizAnswer>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
