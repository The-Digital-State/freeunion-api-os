<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class OrganizationType
 *
 * @property int $id
 * @property string $name
 * @property Collection<int, Organization> $organizations
 */
class OrganizationType extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    /**
     * @return HasMany<Organization>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}
