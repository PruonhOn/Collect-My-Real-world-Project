<?php

namespace App\Http\Api;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(string $message = 'Something went wrong.', mixed $errors = null, int $status = 400): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ];

        return response()->json($payload, $status);
    }
}
