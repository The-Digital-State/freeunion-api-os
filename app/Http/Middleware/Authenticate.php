<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;

class Authenticate extends BaseAuthenticate
{
    /**
     * @param  Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws AuthenticationException
     */
    protected function authenticate($request, array $guards): void
    {
        parent::authenticate($request, $guards);

        $user = $request->user();

        if ($user) {
            $user->timestamps = false;
            $user->last_action_at = now();
            $user->save();
        }
    }
}
