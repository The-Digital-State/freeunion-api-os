<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class DeskImage
 *
 * @property int $id
 * @property int $desk_task_id
 * @property int $user_id
 * @property string|null $image
 * @property Carbon|null $created_at
 * @property DeskTask $deskTask
 * @property User $user
 */
class DeskImage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'image',
    ];

    /**
     * @return BelongsTo<DeskTask, DeskImage>
     */
    public function deskTask(): BelongsTo
    {
        return $this->belongsTo(DeskTask::class);
    }

    /**
     * @return BelongsTo<User, DeskImage>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
