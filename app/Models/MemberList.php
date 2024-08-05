<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class MemberList
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property array $filter
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, User> $members
 * @property Organization $organization
 */
class MemberList extends Model
{
    protected $fillable = ['organization_id', 'name', 'filter'];

    protected $casts = [
        'filter' => 'array',
    ];

    /**
     * @return BelongsToMany<User>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'member_list_user');
    }

    /**
     * @return BelongsTo<Organization, MemberList>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
