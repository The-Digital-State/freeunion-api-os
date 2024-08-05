<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class Material
 *
 * @property int $id
 * @property int $organization_id
 * @property int $m_section_id
 * @property int $user_id
 * @property int $index
 * @property string $type
 * @property string $title
 * @property string|null $excerpt
 * @property string $content
 * @property string|null $preview
 * @property int $visible
 * @property bool $published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $published_at
 * @property MSection $mSection
 * @property Collection<int, NewsTag> $newsTags
 * @property Organization $organization
 * @property User $user
 */
class Material extends Model
{
    public const VISIBLE_ALL = 0;

    public const VISIBLE_USERS = 1;

    public const VISIBLE_MEMBERS = 2;

    public const VISIBLE_ADMINS = 3;

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

    protected $fillable = ['type', 'title', 'excerpt', 'content', 'preview', 'visible'];

    protected $casts = [
        'published' => 'boolean',
    ];

    protected $dates = ['published_at'];

    /**
     * @param  Builder<Material>  $query
     * @param  User|null  $user
     * @return void
     */
    public function scopeVisibled(Builder $query, User|null $user): void
    {
        $query->where('published', true);

        if ($user === null) {
            $query->where('visible', self::VISIBLE_ALL);
        } else {
            $query->where(static function (Builder $q) use ($user) {
                $q->whereIn('visible', [self::VISIBLE_ALL, self::VISIBLE_USERS]);
                $q->orWhere(static function (Builder $q) use ($user) {
                    $q->where('visible', self::VISIBLE_MEMBERS);
                    $q->whereIn('organization_id', $user->membership->pluck('id'));
                });
            });
        }
    }

    public function isVisibled(User|null $user): bool
    {
        return $this->published
            && (
                $this->attributes['visible'] === self::VISIBLE_ALL
                || ($user
                    && (
                        $this->attributes['visible'] === self::VISIBLE_USERS
                        || (
                            $this->attributes['visible'] === self::VISIBLE_MEMBERS
                            && $user->membership->pluck('id')->contains($this->id)
                        )
                    )
                )
            );
    }

    /**
     * @return BelongsTo<MSection, Material>
     */
    public function mSection(): BelongsTo
    {
        return $this->belongsTo(MSection::class);
    }

    /**
     * @return BelongsToMany<NewsTag>
     */
    public function newsTags(): BelongsToMany
    {
        return $this->belongsToMany(NewsTag::class);
    }

    /**
     * @return BelongsTo<Organization, Material>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, Material>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getExcerpt(): string
    {
        $result = $this->excerpt ?? '';

        if ($result === '' && $this->type === 'text') {
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

            $result = $result === '' ?
                explode("\n", wordwrap(strip_tags($content), self::EXCERPT_MAX_LENGTH - 3))[0].'...'
                : mb_substr($result, 1);
        }

        return $result;
    }

    protected static function boot(): void
    {
        parent::boot();

        self::updated(static function (Material $material) {
            if ($material->published && $material->published_at === null) {
                $material->published_at = $material->updated_at;
                $material->save();
            }
        });
    }
}
