<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Reaction
 *
 * @property int $id
 * @property int $user_id
 * @property int $reaction
 * @property Model $model
 * @property User $user
 */
class Reaction extends Model
{
    public const REACTIONS = [
        'thumbs_up',
        'thumbs_down',
        'neutral_face',
    ];

    public $timestamps = false;

    protected $fillable = ['user_id', 'reaction'];

    protected $casts = [
        'reaction' => 'integer',
    ];

    /**
     * @return MorphTo<Model, Reaction>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, Reaction>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getName(): string
    {
        return self::REACTIONS[$this->reaction] ?? '';
    }

    public static function getReaction(int $reaction): string
    {
        return self::REACTIONS[$reaction] ?? '';
    }
}
