<?php

namespace App\Modules\Email\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailProviderAllowance extends Model
{
    use HasFactory;

    protected $table = 'email_provider_allowances';

    protected $fillable = [
        'provider_account_id',
        'allowance_type',
        'allowance_limit',
        'used_count',
        'remaining_count',
        'quota_source',
        'measured_at',
        'reset_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'allowance_limit' => 'integer',
            'used_count' => 'integer',
            'remaining_count' => 'integer',
            'measured_at' => 'datetime',
            'reset_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailProviderAccount::class, 'provider_account_id');
    }

    /**
     * Whether exact remaining quota is unknown / not exposed by provider.
     */
    public function isRemainingUnknown(): bool
    {
        return $this->quota_source === 'UNKNOWN' || $this->remaining_count === null;
    }

    /**
     * Human-friendly quota source description.
     */
    public function quotaSourceBadge(): array
    {
        return match ($this->quota_source) {
            'PROVIDER_REPORTED' => ['label' => 'Provider Reported', 'badge' => 'bg-success text-white'],
            'DOCUMENTED_PLAN' => ['label' => 'Documented Plan', 'badge' => 'bg-info text-dark'],
            'LOCALLY_MEASURED' => ['label' => 'Locally Measured', 'badge' => 'bg-primary text-white'],
            'ESTIMATED' => ['label' => 'Estimated Allowance', 'badge' => 'bg-warning text-dark'],
            default => ['label' => 'Unknown / Not Exposed', 'badge' => 'bg-secondary text-white'],
        };
    }
}

