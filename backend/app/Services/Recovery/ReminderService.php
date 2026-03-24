<?php

declare(strict_types=1);

namespace App\Services\Recovery;

use App\Models\Invoice;
use App\Models\RecoveryRecord;
use App\Models\ReminderLog;

class ReminderService
{
    // Message templates per stage
    const TEMPLATES = [
        1 => "Dear {name}, this is a friendly reminder that your invoice {invoice_no} of ₹{amount} was due on {due_date}. Please make payment at your earliest convenience. {link}",
        2 => "Dear {name}, we noticed your invoice {invoice_no} of ₹{amount} is {days} days overdue. Please pay now using this secure link: {link}. Contact us if you have any questions.",
        3 => "Dear {name}, your account has an overdue balance of ₹{amount} (Invoice: {invoice_no}). Immediate payment is required to avoid further action. Pay now: {link}",
        4 => "FINAL NOTICE — Dear {name}, ₹{amount} on Invoice {invoice_no} remains unpaid. Failure to pay within 7 days may result in legal proceedings. Pay now: {link}",
        5 => "[INTERNAL] Invoice {invoice_no} for ₹{amount} — Customer: {name} — escalated to legal team.",
    ];

    /**
     * Send a recovery reminder via specified channels.
     * All reminders are logged — no silent dispatches.
     */
    public function sendReminder(
        RecoveryRecord $record,
        Invoice $invoice,
        int $stage,
        array $channels,
        ?string $paymentLinkId = null
    ): array {
        $sent = [];

        $message = $this->buildMessage($stage, $invoice, $paymentLinkId);

        foreach ($channels as $channel) {
            $status = $this->dispatch($channel, $invoice, $message);

            /** @var \App\Models\ReminderLog $log */
            $log = ReminderLog::create([
                'recovery_record_id' => $record->id,
                'invoice_id'         => $invoice->id,
                'reminder_stage'     => $stage,
                'channel'            => $channel,
                'status'             => $status['status'],
                'message_content'    => $message,
                'gateway_message_id' => $status['message_id'] ?? null,
                'sent_by'            => 'SYSTEM',
                'sent_at'            => $status['status'] === 'SENT' ? now() : null,
                'error_message'      => $status['error'] ?? null,
            ]);

            $sent[] = [
                'channel'    => $channel,
                'status'     => $status['status'],
                'message_id' => $status['message_id'] ?? null,
            ];
        }

        return $sent;
    }

    private function buildMessage(int $stage, Invoice $invoice, ?string $paymentLinkId): string
    {
        $template = self::TEMPLATES[$stage] ?? self::TEMPLATES[4];

        $link = $paymentLinkId
            ? \App\Models\PaymentLink::find($paymentLinkId)?->short_url ?? ''
            : '';

        return str_replace(
            ['{name}', '{invoice_no}', '{amount}', '{due_date}', '{days}', '{link}'],
            [
                $invoice->customer?->name ?? 'Valued Customer',
                $invoice->invoice_number,
                number_format((float) $invoice->outstanding_balance, 2),
                $invoice->due_date?->format('d/m/Y') ?? 'N/A',
                $invoice->due_date ? now()->diffInDays($invoice->due_date) : 0,
                $link,
            ],
            $template
        );
    }

    private function dispatch(string $channel, Invoice $invoice, string $message): array
    {
        // In production: integrate WhatsApp Business API / SMTP / SMS gateway here
        // Returning a mock successful dispatch for now
        return [
            'status'     => 'SENT',
            'message_id' => 'MSG-' . strtoupper(uniqid()),
        ];
    }
}
