<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class NewsImpression
 *
 * @property int $id
 * @property int $news_id
 * @property int|null $user_id
 * @property string $ip
 * @property News $news
 * @property User|null $user
 */
class NewsImpression extends Model
{
    public $timestamps = false;

    protected $fillable = ['news_id', 'user_id', 'ip'];

    /**
     * @return BelongsTo<News, NewsImpression>
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    /**
     * @return BelongsTo<User, NewsImpression>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
