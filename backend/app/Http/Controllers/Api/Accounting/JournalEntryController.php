<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\PostJournalRequest;
use App\Http\Requests\Accounting\ReverseJournalRequest;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function __construct(
        private readonly JournalService $journalService,
        private readonly LedgerService $ledgerService,
    ) {}

    /** POST /api/journal-entries */
    public function store(PostJournalRequest $request): JsonResponse
    {
        $data = $request->validated();

        $entry = $this->journalService->postJournal($data, $data['lines']);

        return $this->success($entry->load('lines'), 'Journal entry posted.', 201);
    }

    /** GET /api/journal-entries/:id */
    public function show(string $id): JsonResponse
    {
        $entry = JournalEntry::with('lines')->findOrFail($id);
        return $this->success($entry);
    }

    /** POST /api/journal-entries/:id/reverse */
    public function reverse(ReverseJournalRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $reversal = $this->journalService->reverseJournal($id, $data['reason']);

        return $this->success($reversal->load('lines'), 'Reversal entry posted.');
    }

    /** GET /api/ledger/:account_code */
    public function ledger(Request $request, string $accountCode): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'store_id'   => 'nullable|uuid|exists:stores,id',
        ]);

        $ledger = $this->ledgerService->getLedger(
            $accountCode,
            $data['start_date'],
            $data['end_date'],
            $data['store_id'] ?? null
        );

        return $this->success($ledger);
    }

    /** GET /api/trial-balance */
    public function trialBalance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'as_of_date' => 'required|date',
            'store_id'   => 'nullable|uuid|exists:stores,id',
        ]);

        $tb = $this->ledgerService->getTrialBalance($data['as_of_date'], $data['store_id'] ?? null);

        return $this->success($tb);
    }
}
