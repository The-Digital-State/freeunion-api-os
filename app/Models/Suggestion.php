<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\SuggestionNewEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Suggestion
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property int|null $desk_task_id
 * @property string $title
 * @property string|null $description
 * @property string|null $solution
 * @property string|null $goal
 * @property string|null $urgency
 * @property string|null $budget
 * @property string|null $legal_aid
 * @property string|null $rights_violation
 * @property bool $is_closed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property DeskTask|null $deskTask
 * @property Organization $organization
 * @property User|null $user
 */
class Suggestion extends Model
{
    use HasFactory;
    use Traits\HasComments;
    use Traits\HasMedia;
    use Traits\HasReaction;

    protected $fillable = [
        'organization_id',
        'user_id',
        'title',
        'description',
        'solution',
        'goal',
        'urgency',
        'budget',
        'legal_aid',
        'rights_violation',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    /**
     * @return BelongsTo<DeskTask, Suggestion>
     */
    public function deskTask(): BelongsTo
    {
        return $this->belongsTo(DeskTask::class);
    }

    /**
     * @return BelongsTo<Organization, Suggestion>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, Suggestion>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (Suggestion $suggestion) {
            $deskTask = new DeskTask();
            $deskTask->organization_id = $suggestion->organization_id;
            $deskTask->user_id = $suggestion->user_id;
            $deskTask->visibility = DeskTask::VISIBILITY_MEMBERS;

            if ($deskTask->save()) {
                $suggestion->desk_task_id = $deskTask->id;
                $suggestion->save();
            }

            $toIds = new Collection();

            $suggestion->organization->members->each(static function (User $user) use ($suggestion, $toIds) {
                if ($suggestion->user_id !== $user->id) {
                    $toIds->add($user->id);
                }
            });

            event(new SuggestionNewEvent($toIds->all(), $suggestion));
        });

        static::updated(static function (Suggestion $suggestion) {
            if ($suggestion->desk_task_id) {
                /** @var DeskTask $deskTask */
                $deskTask = DeskTask::find($suggestion->desk_task_id);

                $deskTask->title = $suggestion->title;
                $description = [$suggestion->description ?: ''];

                if ($suggestion->solution) {
                    $description[] = "Шаги: $suggestion->solution";
                }

                if ($suggestion->goal) {
                    $description[] = "Кому это поможет: $suggestion->goal";
                }

                if ($suggestion->urgency) {
                    $description[] = "Срочность: $suggestion->urgency";
                }

                if ($suggestion->budget) {
                    $description[] = "Бюджет: $suggestion->budget";
                }

                if ($suggestion->legal_aid) {
                    $description[] = "Юридическая помощь: $suggestion->legal_aid";
                }

                if ($suggestion->rights_violation) {
                    $description[] = "Нарушение прав: $suggestion->rights_violation";
                }

                $deskTask->description = implode("\n", $description);
                $deskTask->save();
            }
        });
    }
}
