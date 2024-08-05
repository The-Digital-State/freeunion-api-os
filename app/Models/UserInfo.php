<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class UserInfo
 *
 * @property int $user_id
 * @property string|null $family
 * @property string|null $name
 * @property string|null $patronymic
 * @property int|null $sex
 * @property Carbon|null $birthday
 * @property string|null $country
 * @property int|null $worktype
 * @property int|null $scope
 * @property string|null $work_place
 * @property string|null $work_position
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $about
 * @property ActivityScope|null $scopeLink
 */
class UserInfo extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'family',
        'name',
        'patronymic',
        'sex',
        'birthday',
        'country',
        'worktype',
        'scope',
        'work_place',
        'work_position',
        'address',
        'phone',
        'about',
    ];

    protected $dates = [
        'birthday',
    ];

    protected $hidden = [
        'user_id',
    ];

    /**
     * @return BelongsTo<ActivityScope, UserInfo>
     */
    public function scopeLink(): BelongsTo
    {
        return $this->belongsTo(ActivityScope::class, 'scope');
    }
}
