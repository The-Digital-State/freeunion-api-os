<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Incognio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OrganizationChat
 *
 * @property int $id
 * @property int $organization_id
 * @property string|null $name
 * @property int $type
 * @property string $value
 * @property array|null $data
 * @property Organization $organization
 * @property bool $needGet
 */
class OrganizationChat extends Model
{
    public const TYPE_SIMPLE = 0;

    public const TYPE_INCOGNIO = 1;

    public $timestamps = false;

    protected $fillable = ['name', 'type', 'value', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @return BelongsTo<Organization, OrganizationChat>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getNeedGetAttribute(): bool
    {
        return $this->type !== self::TYPE_SIMPLE;
    }

    public function getChat(?User $user = null): string
    {
        return match ($this->type) {
            self::TYPE_INCOGNIO => $user ? $this->incognioInvite($user) : '',
            default => $this->value,
        };
    }

    private function incognioInvite(User $user): string
    {
        return Incognio::setBot(str_replace('@', '', $this->value))
            ->generateInvite($user->getPublicFamily().' '.$user->getPublicName());
    }
}
