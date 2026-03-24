<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Reconciliation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reconciliation\ImportStatementRequest;
use App\Models\BankEntry;
use App\Models\BankStatement;
use App\Services\Reconciliation\ReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReconciliationController extends Controller
{
    public function __construct(private readonly ReconciliationService $service) {}

    /** POST /api/reconciliation/import */
    public function import(ImportStatementRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var BankStatement $statement */
        $statement = BankStatement::create([
            'store_id'              => $data['store_id'],
            'bank_name'             => $data['bank_name'],
            'account_number_masked' => $data['account_number_masked'],
            'statement_date'        => $data['statement_date'],
            'opening_balance'       => $data['opening_balance'],
            'closing_balance'       => $data['closing_balance'],
            'status'                => 'IMPORTED',
            'imported_by'           => Auth::id(),
        ]);

        foreach ($data['entries'] as $entry) {
            BankEntry::create(array_merge($entry, ['bank_statement_id' => $statement->id]));
        }

        return $this->success($statement->load('entries'), 'Bank statement imported.', 201);
    }

    /** POST /api/reconciliation/run */
    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_statement_id' => 'required|uuid|exists:bank_statements,id',
        ]);

        $result = $this->service->autoMatch($data['bank_statement_id']);
        return $this->success($result, 'Auto-matching complete.');
    }

    /** GET /api/reconciliation/unmatched */
    public function unmatched(Request $request): JsonResponse
    {
        $entries = BankEntry::where('status', 'PENDING')
            ->when($request->store_id, fn($q) => $q->whereHas('bankStatement', fn($q2) => $q2->where('store_id', $request->store_id)))
            ->with('bankStatement')
            ->get();

        return $this->success($entries);
    }

    /** POST /api/settlements/process/:id */
    public function processSettlement(string $id): JsonResponse
    {
        $settlement = $this->service->processSettlement($id);
        return $this->success($settlement, 'Settlement processed and journal entry posted.');
    }
}
