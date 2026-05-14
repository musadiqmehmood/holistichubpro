<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Success response with data payload.
     *
     * @param mixed  $data    Resource, collection, or arbitrary payload
     * @param string $message Human-readable status message
     * @param int    $code    HTTP status code (200, 201, …)
     */
    protected function success(mixed $data, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Paginated response — wraps Laravel's LengthAwarePaginator
     * in a consistent envelope.
     */
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
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ],
        ]);
    }

    /** 201 Created */
    protected function created(mixed $data, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /** 200 Updated */
    protected function updated(mixed $data, string $message = 'Updated successfully'): JsonResponse
    {
        return $this->success($data, $message, 200);
    }

    /** 200 Deleted */
    protected function deleted(string $message = 'Deleted successfully'): JsonResponse
    {
        return $this->success(null, $message, 200);
    }

    /**
     * Success response with additional metadata.
     * Use when the payload needs extra keys beyond 'data'
     * (e.g. available_taxes, options, etc.).
     *
     * @param mixed  $data    Primary payload
     * @param array  $extra   Additional top-level keys merged into response
     * @param string $message Human-readable status
     */
    protected function successWith(mixed $data, array $extra, string $message = 'OK'): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $extra));
    }

    /**
     * Error response.
     *
     * @param string     $message Human-readable error description
     * @param int        $code    HTTP error code
     * @param array|null $errors  Validation error bag (key → [messages])
     */
    protected function error(string $message, int $code = 400, ?array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }

    /** 404 Not Found */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /** 409 Conflict */
    protected function conflict(string $message = 'Conflict'): JsonResponse
    {
        return $this->error($message, 409);
    }

    /** 422 Unprocessable Entity */
    protected function unprocessable(string $message = 'Validation failed', ?array $errors = null): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /** 500 Server Error */
    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, 500);
    }
}
