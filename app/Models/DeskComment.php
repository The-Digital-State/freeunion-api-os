<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class DeskComment
 *
 * @property int $id
 * @property int $desk_task_id
 * @property int $user_id
 * @property string|null $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property DeskTask $deskTask
 * @property User $user
 */
class DeskComment extends Model
{
    protected $fillable = [
        'comment',
    ];

    /**
     * @return BelongsTo<DeskTask, DeskComment>
     */
    public function deskTask(): BelongsTo
    {
        return $this->belongsTo(DeskTask::class);
    }

    /**
     * @return BelongsTo<User, DeskComment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
