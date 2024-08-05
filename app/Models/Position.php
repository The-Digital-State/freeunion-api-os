<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Class Position
 *
 * @property int $id
 * @property string $name
 * @property Collection<int, Organization> $organizations
 * @property Collection<int, User> $users
 */
class Position extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    /**
     * @return HasManyThrough<Organization>
     */
    public function organizations(): HasManyThrough
    {
        return $this->hasManyThrough(Organization::class, 'membership');
    }

    /**
     * @return HasManyThrough<User>
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, 'membership');
    }
}
