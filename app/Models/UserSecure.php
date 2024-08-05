<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\EncryptCast;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserSecure
 *
 * @property int $user_id
 * @property array $data
 */
class UserSecure extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'user_id';

    protected $casts = [
        'data' => EncryptCast::class,
    ];
}
