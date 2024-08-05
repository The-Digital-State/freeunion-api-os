<?php

declare(strict_types=1);

namespace App\Modules;

use Illuminate\Support\Facades\Facade;

/**
 * Class Incognio
 *
 * @method static Incognio\Incognio setBot(string $botNickname)
 *
 * @see \App\Modules\Incognio\Incognio
 */
class Incognio extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'incognio';
    }
}
