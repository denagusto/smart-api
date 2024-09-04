<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResponseService
{
    /**
     * Creates a standardized JSON response.
     *
     * @param string $status
     * @param string $message
     * @param array|null $data
     * @param int $statusCode
     * @return JsonResponse
     */
    public function createResponse(string $status, string $message, $data = null, int $statusCode = Response::HTTP_OK): ?JsonResponse
    {
        return new JsonResponse([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
}
