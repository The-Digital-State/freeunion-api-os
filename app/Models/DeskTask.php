<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\DeskTaskNewEvent;
use App\Events\SuggestionWorkEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class DeskTask
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property int $column_id
 * @property int $index
 * @property string $title
 * @property string|null $description
 * @property array $checklist
 * @property int $visibility
 * @property bool $can_self_assign
 * @property bool $is_urgent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, DeskComment> $deskComments
 * @property Collection<int, DeskImage> $deskImages
 * @property Organization $organization
 * @property Suggestion|null $suggestion
 * @property User $user
 * @property Collection<int, User> $users
 */
class DeskTask extends Model
{
    use Traits\HasComments;

    public const VISIBILITY_HIDDEN = 0;

    public const VISIBILITY_ALL = 1;

    public const VISIBILITY_MEMBERS = 2;

    protected $fillable = [
        'column_id',
        'title',
        'description',
        'checklist',
        'visibility',
        'can_self_assign',
        'is_urgent',
    ];

    protected $casts = [
        'checklist' => 'array',
        'can_self_assign' => 'boolean',
        'is_urgent' => 'boolean',
    ];

    /**
     * @return HasMany<DeskComment>
     */
    public function deskComments(): HasMany
    {
        return $this->hasMany(DeskComment::class);
    }

    /**
     * @return HasMany<DeskImage>
     */
    public function deskImages(): HasMany
    {
        return $this->hasMany(DeskImage::class);
    }

    /**
     * @return BelongsTo<Organization, DeskTask>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasOne<Suggestion>
     */
    public function suggestion(): HasOne
    {
        return $this->hasOne(Suggestion::class);
    }

    /**
     * @return BelongsTo<User, DeskTask>
     *
     * TODO: Rename to 'owner'
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::saved(static function (DeskTask $deskTask) {
            if (! $deskTask->exists || isset($deskTask->getDirty()['visibility'])) {
                if (in_array($deskTask->visibility, [DeskTask::VISIBILITY_ALL, DeskTask::VISIBILITY_MEMBERS], true)) {
                    $toIds = new \Illuminate\Support\Collection();

                    $deskTask->organization->members->each(static function (User $user) use ($deskTask, $toIds) {
                        if ($deskTask->user_id !== $user->id) {
                            $toIds->add($user->id);
                        }
                    });

                    event(new DeskTaskNewEvent($toIds->all(), $deskTask));
                }
            }
        });

        self::updated(static function (DeskTask $deskTask) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (
                $deskTask->getOriginal('column_id') === 0
                && isset($deskTask->getDirty()['column_id'])
                && $deskTask->suggestion !== null
            ) {
                $deskTask->suggestion->forceFill(['is_closed' => true])->save();

                event(new SuggestionWorkEvent($deskTask->user_id, $deskTask->suggestion));
            }
        });
    }
}
