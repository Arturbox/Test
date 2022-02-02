<?php

namespace App\Providers;

use App\Http\Responses\JSendResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class ResponseServiceProvider extends ServiceProvider
{
    public function boot(JSendResponse $jsonResponse): void
    {
        ResponseFactory::macro('apiSuccess', function (
            $data = null, $object = 0, int $status = Response::HTTP_OK
        ) use ($jsonResponse) {
            return $jsonResponse->successResponse($data, $object, $status);
        });

        ResponseFactory::macro('apiSuccessPaginated', function (
            $data, LengthAwarePaginator $paginator
        ) use ($jsonResponse) {
            return $jsonResponse->successResponsePaginated($data, $paginator);
        });

        ResponseFactory::macro('apiFail', function (
            $data, int $status = Response::HTTP_BAD_REQUEST
        ) use ($jsonResponse) {
            return $jsonResponse->failResponse($data, $status);
        });

        ResponseFactory::macro('apiError', function (
            string $message, int $status = Response::HTTP_INTERNAL_SERVER_ERROR
        ) use ($jsonResponse) {
            return $jsonResponse->errorResponse($message, $status);
        });

        ResponseFactory::macro('apiUnauthenticated', function (
            string $message = 'Unauthenticated.'
        ) use ($jsonResponse) {
            return $jsonResponse->unauthenticatedResponse($message);
        });

        ResponseFactory::macro('apiUnauthorized', function (
            string $message = 'Unauthorized. Access denied.'
        ) use ($jsonResponse) {
            return $jsonResponse->unauthorizedResponse($message);
        });

        ResponseFactory::macro('apiNotFound', function (
            string $message = 'Resource not found.'
        ) use ($jsonResponse) {
            return $jsonResponse->notFoundResponse($message);
        });
    }

    public function register()
    {
        //
    }
}
