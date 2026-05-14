<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function success(mixed $data, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function paginated(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }

    protected function created(mixed $data, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function updated(mixed $data, string $message = 'Updated successfully'): JsonResponse
    {
        return $this->success($data, $message, 200);
    }

    protected function deleted(string $message = 'Deleted successfully'): JsonResponse
    {
        return $this->success(null, $message, 200);
    }

    protected function error(string $message, int $code = 400, ?array $errors = null): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];
        if ($errors !== null) $payload['errors'] = $errors;
        return response()->json($payload, $code);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function conflict(string $message = 'Conflict'): JsonResponse
    {
        return $this->error($message, 409);
    }

    protected function unprocessable(string $message = 'Validation failed', ?array $errors = null): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, 500);
    }
}
