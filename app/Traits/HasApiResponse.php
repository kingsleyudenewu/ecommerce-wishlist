<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

trait HasApiResponse
{
    /**
     * Return a successful response with 200 OK status.
     *
     * @param  mixed  $data  Data to be returned (JsonResource, ResourceCollection, array, etc.)
     * @param  string  $message  Success message
     * @param  array  $headers  Additional headers
     */
    protected function okResponse($data = null, string $message = 'Success', array $headers = []): JsonResponse
    {
        return $this->apiResponse($data, $message, ResponseAlias::HTTP_OK, $headers);
    }

    /**
     * Return a successful response with 201 Created status.
     *
     * @param  mixed  $data  Data to be returned (JsonResource, ResourceCollection, array, etc.)
     * @param  string  $message  Success message
     * @param  array  $headers  Additional headers
     */
    protected function createdResponse($data = null, string $message = 'Resource created successfully', array $headers = []): JsonResponse
    {
        return $this->apiResponse($data, $message, ResponseAlias::HTTP_CREATED, $headers);
    }

    /**
     * Return a 400 Bad Request response.
     *
     * @param  string  $message  Error message
     * @param  mixed  $errors  Additional error details
     * @param  array  $headers  Additional headers
     */
    protected function badRequestResponse(string $message = 'Bad request', $errors = null, array $headers = []): JsonResponse
    {
        return $this->errorResponse($message, ResponseAlias::HTTP_BAD_REQUEST, $errors, $headers);
    }

    /**
     * Return a 401 Unauthorized response.
     *
     * @param  string  $message  Error message
     * @param  mixed  $errors  Additional error details
     * @param  array  $headers  Additional headers
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized', $errors = null, array $headers = []): JsonResponse
    {
        return $this->errorResponse($message, ResponseAlias::HTTP_UNAUTHORIZED, $errors, $headers);
    }

    /**
     * Return a 403 Forbidden response.
     *
     * @param  string  $message  Error message
     * @param  mixed  $errors  Additional error details
     * @param  array  $headers  Additional headers
     */
    protected function forbiddenResponse(string $message = 'Forbidden', $errors = null, array $headers = []): JsonResponse
    {
        return $this->errorResponse($message, ResponseAlias::HTTP_FORBIDDEN, $errors, $headers);
    }

    /**
     * Return a 404 Not Found response.
     *
     * @param  string  $message  Error message
     * @param  mixed  $errors  Additional error details
     * @param  array  $headers  Additional headers
     */
    protected function notFoundResponse(string $message = 'Resource not found', $errors = null, array $headers = []): JsonResponse
    {
        return $this->errorResponse($message, ResponseAlias::HTTP_NOT_FOUND, $errors, $headers);
    }

    /**
     * Return a 422 Unprocessable Entity response.
     *
     * @param  string  $message  Error message
     * @param  mixed  $errors  Additional error details
     * @param  array  $headers  Additional headers
     */
    protected function unprocessableEntityResponse(string $message = 'Validation failed', $errors = null, array $headers = []): JsonResponse
    {
        return $this->errorResponse($message, ResponseAlias::HTTP_UNPROCESSABLE_ENTITY, $errors, $headers);
    }

    /**
     * Return a 500 Internal Server Error response.
     *
     * @param  string  $message  Error message
     * @param  array  $headers  Additional headers
     */
    protected function serverErrorResponse(string $message = 'Server error', ?Throwable $exception = null, array $headers = []): JsonResponse
    {
        $errors = null;

        if ($exception) {
            $this->logException($exception);
        }

        return $this->errorResponse($exception->getMessage() ?: $message, ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, $errors, $headers);
    }

    /**
     * Generic API response builder.
     *
     * @param  mixed  $data  Data to be returned (JsonResource, ResourceCollection, array, etc.)
     * @param  string  $message  Response message
     * @param  int  $statusCode  HTTP status code
     * @param  array  $headers  Additional headers
     */
    protected function apiResponse($data = null, string $message = '', int $statusCode = ResponseAlias::HTTP_OK, array $headers = []): JsonResponse
    {
        $response = [
            'success' => $statusCode < 400,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $this->processResponseData($data);
        }

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Generic error response builder.
     *
     * @param  string  $message  Error message
     * @param  mixed  $errors  Additional error details
     * @param  int  $statusCode  HTTP status code
     * @param  array  $headers  Additional headers
     */
    protected function errorResponse(string $message, int $statusCode = ResponseAlias::HTTP_BAD_REQUEST, $errors = null, array $headers = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $this->formatErrors($errors);
        }

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Handle validation exception.
     */
    protected function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return $this->unprocessableEntityResponse(
            'Validation failed',
            $exception->errors(),
        );
    }

    /**
     * Process response data based on its type.
     *
     * @param  mixed  $data
     */
    protected function processResponseData($data): mixed
    {
        if ($data instanceof ResourceCollection) {
            return $this->processResourceCollection($data);
        }

        if ($data instanceof JsonResource) {
            return $data->resolve();
        }

        return $data;
    }

    protected function processResourceCollection(ResourceCollection $collection): array|\Illuminate\Support\Collection
    {
        // Check if this is a paginated collection
        $isPaginated = false;

        if (method_exists($collection, 'resource')) {
            $resource = $collection->resource;

            // Check if the resource is a paginator instance
            $isPaginated = $resource instanceof \Illuminate\Pagination\LengthAwarePaginator ||
                $resource instanceof \Illuminate\Pagination\Paginator ||
                (method_exists($resource, 'toArray') &&
                    is_array($resourceArray = $resource->toArray()) &&
                    isset($resourceArray['data'], $resourceArray['current_page']));
        }

        // If the collection is paginated, format it with pagination metadata
        if (method_exists($collection->resource, 'toArray')) {
            $paginated = $collection->resource->toArray();

            return [
                'items' => $collection->collection,
                'pagination' => [
                    'total' => $paginated['total'] ?? null,
                    'count' => isset($paginated['to'], $paginated['from']) ?
                        ($paginated['to'] - ($paginated['from'] - 1)) : count($collection->collection),
                    'per_page' => $paginated['per_page'] ?? null,
                    'current_page' => $paginated['current_page'] ?? null,
                    'total_pages' => $paginated['last_page'] ?? null,
                    'links' => [
                        'next' => $paginated['next_page_url'] ?? null,
                        'prev' => $paginated['prev_page_url'] ?? null,
                        'first' => $paginated['first_page_url'] ?? null,
                        'last' => $paginated['last_page_url'] ?? null,
                    ],
                ],
            ];
        }

        // For non-paginated collections (from all() or get() methods),
        // just return the collection items as an array
        return $collection->collection;
    }

    /**
     * Format error messages consistently.
     *
     * @param  mixed  $errors
     */
    protected function formatErrors($errors): array
    {
        if ($errors instanceof MessageBag) {
            return $errors->toArray();
        }

        if (is_string($errors)) {
            return ['general' => [$errors]];
        }

        return $errors;
    }

    /**
     * Handle generic error responses.
     */
    protected function genericErrorResponse(Throwable $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => collect($exception->getTrace())->map(
                fn($trace) => Arr::except($trace, ['args'])
            )->all(),
        ];
    }

    /**
     * Log the exception details.
     */
    protected function logException(Throwable $exception): void
    {
        Log::error($exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => collect($exception->getTrace())->map(fn($trace) => Arr::except($trace, ['args']))->all(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    }
}
