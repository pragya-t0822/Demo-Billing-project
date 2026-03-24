<?php

declare(strict_types=1);

namespace App\Http\Controllers;

abstract class Controller
{
    /** Standard success response: { success: true, data: {...} } */
    protected function success(mixed $data, string $message = 'OK', int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    /** Standard error response: { success: false, error: {...} } */
    protected function error(string $message, int $status = 400, string $code = 'ERROR', mixed $details = null): \Illuminate\Http\JsonResponse
    {
        $payload = ['code' => $code, 'message' => $message];
        if ($details !== null) {
            $payload['details'] = $details;
        }
        return response()->json(['success' => false, 'error' => $payload], $status);
    }

    /** Paginated response with standard pagination envelope */
    protected function paginated(\Illuminate\Pagination\LengthAwarePaginator $paginator): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success'    => true,
            'data'       => $paginator->items(),
            'pagination' => [
                'page'        => $paginator->currentPage(),
                'per_page'    => $paginator->perPage(),
                'total'       => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }
}
