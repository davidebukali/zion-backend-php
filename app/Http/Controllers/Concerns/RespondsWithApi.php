<?php

namespace App\Http\Controllers\Concerns;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

trait RespondsWithApi
{
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        return ApiResponse::success(
            data: $data,
            message: $message,
            status: $status,
            meta: $meta
        );
    }

    protected function error(
        string $message,
        int $status = 400,
        mixed $errors = null
    ): JsonResponse {
        return ApiResponse::error(
            message: $message,
            status: $status,
            errors: $errors
        );
    }
}