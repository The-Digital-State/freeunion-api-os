<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class PaymentTransaction
 *
 * @property int $id
 * @property int $fundraising_id
 * @property string $transaction_id
 * @property int $summ
 * @property bool $payed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Fundraising $fundraising
 */
class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['transaction_id', 'summ', 'payed'];

    protected $casts = [
        'payed' => 'boolean',
    ];

    /**
     * @return BelongsTo<Fundraising, PaymentTransaction>
     */
    public function fundraising(): BelongsTo
    {
        return $this->belongsTo(Fundraising::class);
    }

    /**
     * @param  Builder<PaymentTransaction>  $query
     * @return Builder<PaymentTransaction>
     */
    public function scopePayed(Builder $query): Builder
    {
        return $query->where('payed', true);
    }

    public function getSummAttribute(int $value): float
    {
        return $value / 100;
    }

    public function setSummAttribute(float $value): void
    {
        $this->attributes['summ'] = $value * 100;
    }
}
