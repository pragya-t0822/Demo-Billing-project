<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CancelInvoiceRequest;
use App\Http\Requests\Billing\CreateInvoiceRequest;
use App\Http\Requests\Billing\ProcessPaymentRequest;
use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * InvoiceController — thin HTTP layer.
 * Validation delegated to FormRequest classes; ALL business logic in InvoiceService / PaymentService.
 */
class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly PaymentService $paymentService,
    ) {}

    /** POST /api/invoices */
    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $invoice = $this->invoiceService->createDraft($data);

        return $this->success($invoice->load('lineItems'), 'Invoice draft created.', 201);
    }

    /** GET /api/invoices/:id */
    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with(['lineItems', 'customer', 'payments'])->findOrFail($id);
        return $this->success($invoice);
    }

    /** GET /api/invoices */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with('customer')
            ->when($request->store_id,    fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->status,      fn($q) => $q->where('status', $request->status))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->orderByDesc('invoice_date');

        return $this->paginated($query->paginate($request->integer('per_page', 20)));
    }

    /** PUT /api/invoices/:id/confirm */
    public function confirm(string $id): JsonResponse
    {
        $invoice = $this->invoiceService->confirm($id);
        return $this->success($invoice, 'Invoice confirmed successfully.');
    }

    /** POST /api/invoices/:id/payment */
    public function payment(ProcessPaymentRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $payment = $this->paymentService->processPayment($id, $data);
        return $this->success($payment, 'Payment recorded successfully.', 201);
    }

    /** POST /api/invoices/:id/cancel */
    public function cancel(CancelInvoiceRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $invoice = $this->invoiceService->cancel($id, $data['reason']);
        return $this->success($invoice, 'Invoice cancelled and reversal entry posted.');
    }
}
