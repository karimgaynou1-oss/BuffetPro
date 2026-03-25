<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    /**
     * Get the authenticated hotel user from the request.
     */
    protected function getUser(Request $request): User
    {
        return $request->attributes->get('auth_user');
    }

    /**
     * Get the hotel_id from the JWT payload.
     */
    protected function getHotelId(Request $request): int
    {
        return (int) ($request->attributes->get('jwt_payload')['hotel_id'] ?? 0);
    }

    /**
     * Standard success response.
     */
    protected function success(mixed $data, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Standard paginated response.
     */
    protected function paginated(\Illuminate\Pagination\LengthAwarePaginator $paginator, string $dataKey = 'items'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                $dataKey   => $paginator->items(),
                'meta'     => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Standard error response.
     */
    protected function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $body = ['success' => false, 'error' => $message];
        if ($errors) {
            $body['errors'] = $errors;
        }
        return response()->json($body, $status);
    }
}
