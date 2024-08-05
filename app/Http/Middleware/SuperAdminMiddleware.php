<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SuperAdminMiddleware
{
    /**
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();

        if ($user === null || ! $user->is_admin) {
            throw new AuthorizationException();
        }

        return $next($request);
    }
}
