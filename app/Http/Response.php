<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class Response
{
    public static function noContent(): JsonResponse
    {
        return new JsonResponse([], ResponseCode::HTTP_NO_CONTENT);
    }

    public static function success(array $data = []): JsonResponse
    {
        return new JsonResponse(['ok' => true] + $data);
    }

    public static function notImplemented(): JsonResponse
    {
        return self::error('Not Implemented', ResponseCode::HTTP_NOT_IMPLEMENTED);
    }

    public static function error(
        string|array|null $errors,
        int $status = ResponseCode::HTTP_BAD_REQUEST,
    ): JsonResponse {
        if ($errors === null) {
            $errors = '';
        }

        if (! is_array($errors)) {
            $errors = [$errors];
        }

        return new JsonResponse(
            [
                'ok' => false,
                'errors' => $errors,
            ],
            $status ?: ResponseCode::HTTP_BAD_REQUEST
        );
    }
}
