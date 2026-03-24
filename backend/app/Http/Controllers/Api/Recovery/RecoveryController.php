<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Recovery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recovery\GeneratePaymentLinkRequest;
use App\Services\Recovery\RecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecoveryController extends Controller
{
    public function __construct(private readonly RecoveryService $recoveryService) {}

    /** GET /api/recovery/overdue */
    public function overdue(Request $request): JsonResponse
    {
        $data = $this->recoveryService->detectOverdue($request->store_id);
        return $this->success($data);
    }

    /** POST /api/recovery/run-cycle */
    public function runCycle(Request $request): JsonResponse
    {
        $result = $this->recoveryService->runCycle($request->store_id);
        return $this->success($result, 'Recovery cycle completed.');
    }

    /** POST /api/recovery/payment-links */
    public function generateLink(GeneratePaymentLinkRequest $request): JsonResponse
    {
        $data = $request->validated();

        $link = $this->recoveryService->generatePaymentLink(
            $data['invoice_id'],
            $data['customer_id'] ?? null,
            $data['amount'],
            $data['expiry_hours'] ?? 48
        );

        return $this->success($link, 'Payment link generated.', 201);
    }
}
