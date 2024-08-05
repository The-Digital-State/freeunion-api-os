<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class PaymentSystem
 *
 * @property int $id
 * @property int $organization_id
 * @property string $payment_system
 * @property array $credentials
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Fundraising> $fundraisings
 * @property Organization $organization
 */
class PaymentSystem extends Model
{
    public const PAYMENT_SYSTEM_STRIPE = 'stripe';

    protected $fillable = ['payment_system', 'credentials', 'active'];

    protected $casts = [
        'credentials' => 'array',
        'active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<Fundraising>
     */
    public function fundraisings(): BelongsToMany
    {
        return $this->belongsToMany(Fundraising::class)->withPivot('product_id');
    }

    /**
     * @return BelongsTo<Organization, PaymentSystem>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function possiblePaymentSystems(): array
    {
        return [
            self::PAYMENT_SYSTEM_STRIPE,
        ];
    }
}
