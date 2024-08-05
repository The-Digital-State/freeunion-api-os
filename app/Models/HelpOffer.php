<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class HelpOffer
 *
 * @property int $id
 * @property string $text
 * @property int|null $organization_id
 * @property bool $enabled
 * @property Collection<int, HelpOfferLink> $helpOfferLinks
 * @property Organization $organization
 */
class HelpOffer extends Model
{
    public $timestamps = false;

    protected $fillable = ['text', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * @return HasMany<HelpOfferLink>
     */
    public function helpOfferLinks(): HasMany
    {
        return $this->hasMany(HelpOfferLink::class);
    }

    /**
     * @return BelongsTo<Organization, HelpOffer>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function defaultHelpOffers(): array
    {
        return [
            'Вести соцсети',
            'Привлекать новых участников',
            'Модерировать чаты',
            'Быть лидером',
            'Отвечать на сообщения',
            'Проверять почту',
        ];
    }
}
