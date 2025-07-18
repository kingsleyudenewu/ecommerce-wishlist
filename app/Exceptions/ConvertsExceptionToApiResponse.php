<?php

namespace App\Exceptions;

use App\Traits\HasApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Throwable;

class ConvertsExceptionToApiResponse
{
    use HasApiResponse;

    /**
     * Convert the exception to a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function renderApiResponse($request, Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return $this->validationErrorResponse($exception);
        }

        if ($exception instanceof AuthenticationException || $exception instanceof UnauthorizedException) {
            return $this->unauthorizedResponse($exception);
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->notFoundResponse('Resource not found', $exception->getMessage());
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return $this->errorResponse($exception);
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            return $this->forbiddenResponse($exception);
        }

        if ($exception instanceof HttpResponseException) {
            return $this->errorResponse('Error', 400, $exception);
        }

        return $this->serverErrorResponse('Server error', $exception);
    }
}
