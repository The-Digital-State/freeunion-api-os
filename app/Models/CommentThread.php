<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class CommentThread
 *
 * @property int $id
 * @property array|null $data
 * @property Collection<int, Comment> $comments
 * @property Model $model
 */
class CommentThread extends Model
{
    public $timestamps = false;

    protected $fillable = ['data'];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @return MorphTo<Model, CommentThread>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<Comment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
