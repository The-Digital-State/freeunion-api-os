<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * TODO: Change return type
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        /** @var Organization|null $organization */
        $organization = $request->route('organization');

        if (
            $user === null
            || (
                $organization !== null
                && ! in_array($organization->id, $user->organizationsAdminister()->get()->pluck('id')->toArray(), true)
            )
        ) {
            throw new AuthorizationException();
        }

        return $next($request);
    }
}
