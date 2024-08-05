<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class HelpOfferLink
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property int $help_offer_id
 * @property HelpOffer $helpOffer
 * @property Organization $organization
 * @property User $user
 */
class HelpOfferLink extends Model
{
    public $timestamps = false;

    protected $fillable = ['organization_id', 'user_id', 'help_offer_id'];

    /**
     * @return BelongsTo<HelpOffer, HelpOfferLink>
     */
    public function helpOffer(): BelongsTo
    {
        return $this->belongsTo(HelpOffer::class);
    }

    /**
     * @return BelongsTo<Organization, HelpOfferLink>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, HelpOfferLink>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
