<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Notification
 *
 * @property int $id
 * @property int $to_id
 * @property string $type
 * @property string|null $title
 * @property string|null $message
 * @property array|null $data
 * @property int $status
 * @property Carbon|null $created_at
 * @property Organization|null $organization
 * @property User|null $user
 */
class Notification extends Model
{
    public const STATUS_NOTREAD = 0;

    public const STATUS_READ = 1;

    public const UPDATED_AT = null;

    protected $fillable = [
        'from_id',
        'to_id',
        'type',
        'title',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeNotRead(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NOTREAD);
    }

    public function setRead(): void
    {
        $this->status = self::STATUS_READ;
        $this->save();
    }

    /**
     * @return BelongsTo<Organization, Notification>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'from_id');
    }

    /**
     * @return BelongsTo<User, Notification>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public static function send(int $to, string $type, string $title, string $message, array $data = []): void
    {
        self::query()->create([
            'to_id' => $to,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
