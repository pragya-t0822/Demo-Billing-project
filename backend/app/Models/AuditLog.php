<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    // Append-only: disable update/delete
    public const UPDATED_AT = null;

    protected $fillable = [
        'log_number', 'user_id', 'store_id', 'action',
        'entity_type', 'entity_id', 'before_state', 'after_state',
        'ip_address', 'user_agent', 'severity', 'logged_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state'  => 'array',
        'logged_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Prevent updates — audit logs are immutable
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \RuntimeException('Audit logs are immutable and cannot be updated.');
        }

        return parent::save($options);
    }
}
