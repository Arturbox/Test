<?php

namespace App\Tools;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class APIResponse
 * @package App\Common\Tools
 */
class APIResponse
{
    /**
     * @param array $response
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    public static function successResponse(
        $response = [],
        string $message = 'Successful request.',
        int $status = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'status' => 'success',
            'data' => $response,
        ], $status);
    }

    /**
     * @param $response
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    public static function collectionResponse(
        $response = [],
        string $message = 'Successful request.',
        int $status = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'status' =>  'success',
            'data' => $response->resource,
            'pagination' => $response->pagination,
        ], $status);
    }

    /**
     * @param array $response
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    public static function errorResponse(
        array $response = [],
        string $message = 'Error request.',
        int $status = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'status' => 'error',
            'error' => $response
        ], $status);
    }
}
