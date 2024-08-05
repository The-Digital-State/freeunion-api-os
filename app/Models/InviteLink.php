<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class InviteLink
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $organization_id
 * @property string $code
 * @property Carbon|null $created_at
 * @property Carbon|null $deleted_at
 * @property Organization|null $organization
 * @property User $user
 */
class InviteLink extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const UPDATED_AT = null;

    public const INTERVAL_HOUR = 24;

    public const MAX_LINKS = 100;

    /**
     * @return BelongsTo<Organization, InviteLink>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, InviteLink>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        if ($this->created_at !== null) {
            return $this->created_at->addHours(self::INTERVAL_HOUR)->isBefore(Carbon::now());
        }

        return true;
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (InviteLink $link) {
            $link->code = Str::random(64);
        });
    }
}
