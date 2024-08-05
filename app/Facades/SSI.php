<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static object|false auth(mixed $request)
 * @method static array|false membership(mixed $request)
 * @method static object|false trusted(mixed $request)
 *
 * @see \App\Services\Auth\SSI
 */
class SSI extends Facade
{
    /**
     * @return class-string
     */
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Auth\SSI::class;
    }
}
