<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Fundraising
 *
 * @property int $id
 * @property int $organization_id
 * @property string $title
 * @property string|null $description
 * @property string|null $image
 * @property int|null $ammount
 * @property string|null $currency
 * @property bool $is_subscription
 * @property Carbon|null $date_end
 * @property array|null $manual_payments
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Organization $organization
 * @property Collection<int, PaymentSystem> $paymentSystems
 * @property Collection<int, PaymentTransaction> $transactions
 */
class Fundraising extends Model
{
    public const PAYMENT_SYSTEM_PAYPAL = 'paypal';

    public const PAYMENT_SYSTEM_PATREON = 'patreon';

    public const PAYMENT_SYSTEM_STRIPE = 'stripe';

    public const PAYMENT_SYSTEM_CRYPTO = 'crypto';

    protected $fillable = [
        'title',
        'description',
        'image',
        'ammount',
        'currency',
        'is_subscription',
        'date_end',
        'manual_payments',
    ];

    protected $casts = [
        'is_subscription' => 'boolean',
        'manual_payments' => 'array',
    ];

    /**
     * @return BelongsTo<Organization, Fundraising>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsToMany<PaymentSystem>
     */
    public function paymentSystems(): BelongsToMany
    {
        $query = $this->belongsToMany(PaymentSystem::class);
        $query->where('active', true)
            ->withPivot('product_id');

        return $query;
    }

    /**
     * @return HasMany<PaymentTransaction>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * @param  Builder<Fundraising>  $query
     * @return Builder<Fundraising>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('date_end', '>=', Carbon::now())->orWhereNull('date_end');
    }

    public function getAmmountAttribute(int|null $value): float
    {
        return ($value ?? 0) / 100;
    }

    public function setAmmountAttribute(float $value): void
    {
        $this->attributes['ammount'] = $value * 100;
    }

    public static function possiblePaymentLinkSystems(): array
    {
        return [
            self::PAYMENT_SYSTEM_PAYPAL,
            self::PAYMENT_SYSTEM_PATREON,
            self::PAYMENT_SYSTEM_STRIPE,
            self::PAYMENT_SYSTEM_CRYPTO,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('order', static function (Builder $builder) {
            $builder->orderBy('id', 'desc');
        });
    }
}
