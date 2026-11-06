<?php

namespace App\Modules\Email\app\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class EmailAccount extends Model
{
    use HasFactory;

    protected $table = 'email_accounts';

    protected $fillable = [
        'user_id',
        'provider_type',
        'email_address',
        'sender_name',
        'encrypted_credentials',
        'daily_cap',
        'sent_today',
        'last_reset_date',
        'is_active',
        'is_default',
        'pause_reason',
    ];

    protected function casts(): array
    {
        return [
            'daily_cap' => 'integer',
            'sent_today' => 'integer',
            'last_reset_date' => 'date',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'email_account_id')->latest();
    }

    /**
     * Retrieve decrypted secrets payload.
     */
    public function getSecrets(): array
    {
        if (empty($this->encrypted_credentials)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($this->encrypted_credentials);
            return json_decode($decrypted, true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Encrypt and store secrets payload.
     */
    public function setSecrets(array $secrets): void
    {
        $this->encrypted_credentials = Crypt::encryptString(json_encode($secrets));
    }

    /**
     * Reset quota if today is a new calendar day.
     */
    public function resetQuotaIfNewDay(): void
    {
        $today = Carbon::today();
        if (!$this->last_reset_date || $this->last_reset_date->lt($today)) {
            $this->update([
                'sent_today' => 0,
                'last_reset_date' => $today,
                'pause_reason' => null,
            ]);
        }
    }

    /**
     * Remaining quota available for today.
     */
    public function getRemainingQuotaAttribute(): int
    {
        $this->resetQuotaIfNewDay();
        return max(0, $this->daily_cap - $this->sent_today);
    }
}

