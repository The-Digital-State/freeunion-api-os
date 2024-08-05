<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Response;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if ($this->shouldReport($e) && app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }

    public function render($request, Throwable $e): JsonResponse
    {
        if ($e instanceof AuthenticationException || $e instanceof AuthorizationException) {
            if (Auth::user()) {
                if (config('app.debug')) {
                    return Response::error(__('auth.forbidden'), ResponseCode::HTTP_FORBIDDEN);
                }

                return Response::error(__('model.not_found'), ResponseCode::HTTP_NOT_FOUND);
            }

            return Response::error(__('auth.need_auth'), ResponseCode::HTTP_UNAUTHORIZED);
        }

        if ($e instanceof ModelNotFoundException) {
            return Response::error(__('model.not_found'), ResponseCode::HTTP_NOT_FOUND);
        }

        if ($e instanceof PostTooLargeException) {
            return Response::error(
                __('common.post_max', ['max' => ini_get('post_max_size')]),
                ResponseCode::HTTP_NOT_FOUND
            );
        }

        if ($e instanceof ThrottleRequestsException) {
            return Response::error(
                __('errors.throttle'),
                ResponseCode::HTTP_TOO_MANY_REQUESTS
            );
        }

        if (config('app.debug')) {
            return Response::error(explode("\n", $e->getMessage()."\n".$e->getTraceAsString()));
        }

        $message = $e->getMessage();
        $code = $e->getCode();

        return Response::error($message !== '' ? $message : __('errors.unknown'), is_int($code) ? $code : 0);
    }
}
