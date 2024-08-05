<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\SuggestionCommentNewAnswerEvent;
use App\Events\SuggestionCommentNewEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Comment
 *
 * @property int $id
 * @property int $comment_thread_id
 * @property int $comment_id
 * @property int $user_id
 * @property string $comment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property CommentThread $commentThread
 * @property Collection<int, Comment> $comments
 * @property Comment $parentComment
 * @property User $user
 */
class Comment extends Model
{
    use SoftDeletes;
    use Traits\HasReaction;

    protected $fillable = ['comment_id', 'user_id', 'comment'];

    /**
     * @return BelongsTo<CommentThread, Comment>
     */
    public function commentThread(): BelongsTo
    {
        return $this->belongsTo(CommentThread::class);
    }

    /**
     * @return HasMany<Comment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(__CLASS__);
    }

    /**
     * @return BelongsTo<Comment, Comment>
     */
    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'comment_id');
    }

    /**
     * @return BelongsTo<User, Comment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (Comment $comment) {
            $model = $comment->commentThread->model;

            if ($model instanceof Suggestion) {
                if ($comment->commentThread->comments()->count() === 1) {
                    event(new SuggestionCommentNewEvent($comment));
                }

                if (
                    $comment->comment_id !== null
                    && $comment->user_id !== $comment->parentComment->user_id
                    && $comment->parentComment->comments()->count() === 1
                ) {
                    event(new SuggestionCommentNewAnswerEvent($comment));
                }
            }
        });
    }
}
