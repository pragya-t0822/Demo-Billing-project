<?php

declare(strict_types=1);

namespace App\Services\Recovery;

use App\Models\Invoice;
use App\Models\PaymentLink;
use App\Models\RecoveryRecord;
use App\Services\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * RecoveryService — orchestrates the automated collections workflow.
 *
 * Escalation stages:
 *   Stage 1 (1–7 days):   WhatsApp soft reminder
 *   Stage 2 (8–15 days):  WhatsApp + Email + payment link
 *   Stage 3 (16–30 days): Firm notice + call flag
 *   Stage 4 (31–60 days): Final notice + legal warning
 *   Stage 5 (60+ days):   Legal escalation (internal only)
 */
class RecoveryService
{
    const STAGE_THRESHOLDS = [1 => 7, 2 => 15, 3 => 30, 4 => 60];

    public function __construct(
        private readonly ReminderService $reminderService,
        private readonly AuditService $audit,
    ) {}

    /**
     * Detect all overdue invoices and return classified buckets.
     */
    public function detectOverdue(?string $storeId = null, string $asOfDate = null): array
    {
        $asOfDate ??= now()->toDateString();

        $query = Invoice::with('customer')
            ->whereIn('status', ['CONFIRMED', 'PARTIAL'])
            ->where('due_date', '<', $asOfDate)
            ->whereDoesntHave('recoveryRecord', fn($q) => $q->whereIn('status', ['CLOSED', 'LEGAL', 'WRITTEN_OFF']));

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $invoices = $query->get();

        $buckets = ['1_7' => [], '8_15' => [], '16_30' => [], '31_60' => [], '60_plus' => []];
        $totalAmount = 0;

        foreach ($invoices as $invoice) {
            $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays(now());
            $outstanding = (float) $invoice->outstanding_balance;
            $totalAmount += $outstanding;

            $row = [
                'invoice_id'          => $invoice->id,
                'invoice_number'      => $invoice->invoice_number,
                'customer_name'       => $invoice->customer?->name ?? 'Walk-in',
                'customer_mobile'     => $invoice->customer?->phone,
                'outstanding_balance' => $outstanding,
                'due_date'            => $invoice->due_date?->toDateString() ?? $asOfDate,
                'days_overdue'        => $daysOverdue,
            ];

            match (true) {
                $daysOverdue <= 7  => $buckets['1_7'][] = $row,
                $daysOverdue <= 15 => $buckets['8_15'][] = $row,
                $daysOverdue <= 30 => $buckets['16_30'][] = $row,
                $daysOverdue <= 60 => $buckets['31_60'][] = $row,
                default            => $buckets['60_plus'][] = $row,
            };
        }

        return [
            'scan_date'            => $asOfDate,
            'total_overdue_count'  => $invoices->count(),
            'total_overdue_amount' => round($totalAmount, 2),
            'buckets'              => $buckets,
        ];
    }

    /**
     * Run the automated recovery cycle for a store.
     * Called by daily CRON at 08:00.
     */
    public function runCycle(?string $storeId = null): array
    {
        $overdue     = $this->detectOverdue($storeId);
        $processed   = 0;
        $reminders   = 0;
        $linksGenerated = 0;

        // Flatten buckets safely — array_merge(...) with empty spread would throw
        $allInvoices = array_merge([], ...array_values($overdue['buckets']));

        foreach ($allInvoices as $row) {
            /** @var Invoice|null $invoice */
            $invoice = Invoice::find($row['invoice_id']);
            if (! $invoice) continue;

            // Upsert recovery record
            /** @var RecoveryRecord $record */
            $record = RecoveryRecord::firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'customer_id'         => $invoice->customer_id,
                    'store_id'            => $invoice->store_id,
                    'outstanding_balance' => $invoice->outstanding_balance,
                    'due_date'            => $invoice->due_date,
                    'days_overdue'        => $row['days_overdue'],
                ]
            );

            // Update days_overdue
            $stage = $this->determineStage($row['days_overdue']);
            $record->update([
                'days_overdue'   => $row['days_overdue'],
                'recovery_stage' => $stage,
                'outstanding_balance' => $invoice->outstanding_balance,
            ]);

            // Stage 5 — legal, no automated messages
            if ($stage >= 5) {
                $record->update(['status' => 'LEGAL']);
                $processed++;
                continue;
            }

            // Suppress if already reminded today for this stage
            if ($record->last_reminder_sent_at
                && $record->last_reminder_sent_at->isToday()
                && $record->recovery_stage >= $stage) {
                continue;
            }

            // Generate payment link for stage 2+
            $link = null;
            if ($stage >= 2) {
                $link = $this->generatePaymentLink($invoice->id, $invoice->customer_id, (float) $invoice->outstanding_balance);
                $linksGenerated++;
            }

            // Send reminder
            $channels = $stage === 1 ? ['WHATSAPP'] : ['WHATSAPP', 'EMAIL'];
            $this->reminderService->sendReminder($record, $invoice, $stage, $channels, $link?->id);
            $record->update(['last_reminder_sent_at' => now()]);

            $reminders++;
            $processed++;
        }

        return [
            'invoices_processed' => $processed,
            'reminders_sent'     => $reminders,
            'links_generated'    => $linksGenerated,
            'run_at'             => now()->toIso8601String(),
        ];
    }

    /**
     * Generate a secure, expiring payment link.
     */
    public function generatePaymentLink(
        string $invoiceId,
        ?string $customerId,
        float $amount,
        int $expiryHours = 48
    ): PaymentLink {
        $linkNumber = $this->generateLinkNumber();

        // Short URL token (in production: use gateway API like Razorpay payment links)
        $token    = Str::random(8);
        $shortUrl = config('app.url') . '/pay/' . $token;

        return PaymentLink::create([
            'link_number'      => $linkNumber,
            'invoice_id'       => $invoiceId,
            'customer_id'      => $customerId,
            'short_url'        => $shortUrl,
            'amount'           => $amount,
            'payment_methods'  => ['UPI', 'CARD', 'NETBANKING'],
            'expires_at'       => now()->addHours($expiryHours),
            'status'           => 'ACTIVE',
            'created_by'       => 'SYSTEM',
        ]);
    }

    private function determineStage(int $daysOverdue): int
    {
        return match (true) {
            $daysOverdue <= 7  => 1,
            $daysOverdue <= 15 => 2,
            $daysOverdue <= 30 => 3,
            $daysOverdue <= 60 => 4,
            default            => 5,
        };
    }

    private function generateLinkNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $last = DB::table('payment_links')
                ->where('link_number', 'like', "PL-{$year}-%")
                ->orderByDesc('link_number')
                ->lockForUpdate()
                ->value('link_number');

            $nextSeq = $last ? (int) substr($last, -5) + 1 : 1;
            return sprintf('PL-%d-%05d', $year, $nextSeq);
        });
    }
}
