<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class NewsTag
 *
 * @property int $id
 * @property string $tag
 * @property int $count
 * @property Carbon $last_published_at
 * @property Collection<int, Material> $materials
 * @property Collection<int, News> $news
 * @property int $materials_count
 * @property int $news_count
 * @property Carbon $materials_max_published_at
 * @property Carbon $news_max_published_at
 */
class NewsTag extends Model
{
    public $timestamps = false;

    protected $fillable = ['tag'];

    /**
     * @return BelongsToMany<Material>
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class);
    }

    /**
     * @return BelongsToMany<News>
     */
    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class);
    }
}
