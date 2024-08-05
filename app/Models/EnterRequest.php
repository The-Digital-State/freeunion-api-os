<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class EnterRequest
 *
 * @property int $id
 * @property int $user_id
 * @property int $organization_id
 * @property string|null $comment
 * @property int $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Organization $organization
 * @property User $user
 */
class EnterRequest extends Model
{
    public const STATUS_REQUESTED = 0;

    public const STATUS_CANCEL = 9;

    public const STATUS_ACTIVE = 10;

    public const STATUS_REJECTED = 20;

    public const STATUS_LEFT = 21;

    public const STATUS_KICK = 22;

    protected $fillable = [
        'user_id',
        'organization_id',
        'comment',
        'status',
    ];

    /**
     * @return BelongsTo<Organization, EnterRequest>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, EnterRequest>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<EnterRequest>  $query
     * @return Builder<EnterRequest>
     */
    public function scopeOnlyRequest(Builder $query): Builder
    {
        return $query->where('status', '<', self::STATUS_ACTIVE);
    }

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_REQUESTED => __('model.request_statuses.requested'),
            self::STATUS_CANCEL => __('model.request_statuses.cancel'),
            self::STATUS_ACTIVE => __('model.request_statuses.active'),
            self::STATUS_REJECTED => __('model.request_statuses.rejected'),
            self::STATUS_LEFT => __('model.request_statuses.left'),
            self::STATUS_KICK => __('model.request_statuses.kick'),
        ];
    }
}
