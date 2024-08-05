<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\NewsOwnPublishedEvent;
use App\Events\NewsPublishedEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class News
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property string $title
 * @property string $content
 * @property string|null $preview
 * @property int $visible
 * @property bool $published
 * @property string|null $comment
 * @property bool $featured
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $published_at
 * @property Collection<int, NewsAbuse> $abuses
 * @property Collection<int, NewsClick> $clicks
 * @property Collection<int, NewsImpression> $impressions
 * @property Collection<int, NewsTag> $newsTags
 * @property Organization $organization
 * @property User $user
 */
class News extends Model
{
    public const VISIBLE_ALL = 0;

    public const VISIBLE_USERS = 1;

    public const VISIBLE_MEMBERS = 2;

    public const EXCERPT_MAX_LENGTH = 155;

    public const NEWLINE_TAGS = [
        '<h1>',
        '<h2>',
        '<h3>',
        '<h4>',
        '<h5>',
        '<h6>',
        '</h1>',
        '</h2>',
        '</h3>',
        '</h4>',
        '</h5>',
        '</h6>',
        '<p>',
        '</p>',
        '<br>',
        '<br/>',
        '<br />',
    ];

    protected $fillable = ['title', 'content', 'preview', 'visible', 'comment'];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
    ];

    protected $dates = ['published_at'];

    /**
     * @return HasMany<NewsAbuse>
     */
    public function abuses(): HasMany
    {
        return $this->hasMany(NewsAbuse::class);
    }

    /**
     * @return HasMany<NewsClick>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(NewsClick::class);
    }

    /**
     * @return HasMany<NewsImpression>
     */
    public function impressions(): HasMany
    {
        return $this->hasMany(NewsImpression::class);
    }

    /**
     * @return BelongsToMany<NewsTag>
     */
    public function newsTags(): BelongsToMany
    {
        return $this->belongsToMany(NewsTag::class);
    }

    /**
     * @return BelongsTo<Organization, News>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, News>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPreview(): string|null
    {
        $img = $this->preview;

        if ($img === null && preg_match_all('/<([^\s>]+)[^>]*?src=[\'"](.*?)[\'"]/i', $this->content, $match)) {
            for ($ind = 0, $iMax = count($match[0]); $ind < $iMax; $ind++) {
                switch (mb_strtolower($match[1][$ind])) {
                    case 'img':
                        $img = $match[2][$ind];

                        break;
                    case 'iframe':
                        if (preg_match('/youtube\.com\/embed\/(\S+)/i', $match[2][$ind], $mt)) {
                            $img = "https://img.youtube.com/vi/$mt[1]/maxresdefault.jpg";
                        }

                        if (preg_match('/youtube\.com\/watch\?v=(\S+)/i', $match[2][$ind], $mt)) {
                            $img = "https://img.youtube.com/vi/$mt[1]/maxresdefault.jpg";
                        }

                        break;
                }

                if ($img !== null) {
                    break;
                }
            }
        }

        return $img;
    }

    public function getExcerpt(): string
    {
        $result = '';

        $content = html_entity_decode($this->content);

        $clearedText = strip_tags(
            str_ireplace(self::NEWLINE_TAGS, "\n", $content)
        );
        $textParts = explode("\n", $clearedText);

        foreach ($textParts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (mb_strlen($result."\n".$part) > self::EXCERPT_MAX_LENGTH) {
                break;
            }

            $result .= "\n".$part;
        }

        return $result === '' ?
            explode("\n", wordwrap(strip_tags($content), self::EXCERPT_MAX_LENGTH - 3))[0].'...'
            : mb_substr($result, 1);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::updated(static function (News $news) {
            if ($news->published && $news->published_at === null) {
                $news->published_at = $news->updated_at;
                $news->save();

                $toIds = new \Illuminate\Support\Collection();
                $toOwnIds = new \Illuminate\Support\Collection();

                $news->organization->members->each(static function (User $user) use ($news, $toIds, $toOwnIds) {
                    if ($news->user_id !== $user->id) {
                        $toIds->add($user->id);
                    } else {
                        $toOwnIds->add($user->id);
                    }
                });

                if ($toIds->count() > 0) {
                    event(new NewsPublishedEvent($toIds->all(), $news));
                }

                if ($toOwnIds->count() > 0) {
                    event(new NewsOwnPublishedEvent($toOwnIds->all(), $news));
                }
            }
        });
    }
}
