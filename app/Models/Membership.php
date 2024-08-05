<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class Membership
 *
 * @property int $user_id
 * @property int $organization_id
 * @property int|null $position_id
 * @property string|null $position_name
 * @property string|null $description
 * @property int $permissions
 * @property string|null $comment
 * @property int $points
 * @property Carbon $joined_at
 */
class Membership extends Model
{
    protected $table = '';

    protected $dates = ['joined_at'];
}
