<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Expense
 *
 * @property int $id
 * @property int $organization_id
 * @property array $data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Organization $organization
 */
class Expense extends Model
{
    use HasFactory;

    protected $fillable = ['payment_system', 'credentials', 'active'];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @return BelongsTo<Organization, Expense>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
