<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class NewsAbuse
 *
 * @property int $id
 * @property int $news_id
 * @property int $type_id
 * @property string $message
 * @property Carbon|null $created_at
 * @property News $news
 */
class NewsAbuse extends Model
{
    public const TYPE_ERROR = 1;

    public const TYPE_FALSE = 2;

    public const TYPE_OFFENSIVE = 3;

    public const TYPE_COMMERCIAL = 4;

    public const UPDATED_AT = null;

    protected $fillable = ['type_id', 'message'];

    /**
     * @return BelongsTo<News, NewsAbuse>
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public static function types(): array
    {
        return [
            self::TYPE_ERROR,
            self::TYPE_FALSE,
            self::TYPE_OFFENSIVE,
            self::TYPE_COMMERCIAL,
        ];
    }
}
