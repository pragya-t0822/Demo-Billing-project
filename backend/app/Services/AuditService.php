<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * AuditService — writes append-only audit log entries for every financial action.
 * Every financial event MUST be logged via this service.
 */
class AuditService
{
    public function log(
        string $action,
        string $entityType,
        string $entityId,
        ?array $beforeState = null,
        ?array $afterState = null,
        string $severity = 'INFO',
        ?string $storeId = null
    ): AuditLog {
        $logNumber = $this->generateLogNumber();

        return AuditLog::create([
            'log_number'   => $logNumber,
            'user_id'      => Auth::id(),  // null for system/cron actions (column is nullable)
            'store_id'     => $storeId,
            'action'       => $action,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'before_state' => $beforeState,
            'after_state'  => $afterState,
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::userAgent(),
            'severity'     => $severity,
            'logged_at'    => now(),
        ]);
    }

    public function logCritical(
        string $action,
        string $entityType,
        string $entityId,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $storeId = null
    ): AuditLog {
        return $this->log($action, $entityType, $entityId, $beforeState, $afterState, 'CRITICAL', $storeId);
    }

    private function generateLogNumber(): string
    {
        // lockForUpdate inside transaction prevents duplicate log numbers
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $year = now()->year;
            $last = AuditLog::whereYear('logged_at', $year)
                ->orderByDesc('log_number')
                ->lockForUpdate()
                ->value('log_number');

            $nextSeq = $last ? (int) substr($last, -5) + 1 : 1;

            return sprintf('AL-%d-%05d', $year, $nextSeq);
        });
    }
}
