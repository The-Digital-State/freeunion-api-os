<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class InterestScope
 *
 * @property int $id
 * @property string $name
 * @property Collection<int, Organization> $organizations
 */
class InterestScope extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    /**
     * @return BelongsToMany<Organization>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class);
    }
}
