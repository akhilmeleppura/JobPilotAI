<?php

namespace App\Modules\Email\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class EmailProviderAccount extends Model
{
    use HasFactory;

    protected $table = 'email_provider_accounts';

    protected $fillable = [
        'user_id',
        'provider',
        'account_email',
        'plan_name',
        'connection_status',
        'policy_status',
        'enabled',
        'priority',
        'is_primary',
        'is_paused',
        'pause_reason',
        'custom_safety_cap',
        'encrypted_credentials',
        'last_synced_at',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'priority' => 'integer',
            'is_primary' => 'boolean',
            'is_paused' => 'boolean',
            'custom_safety_cap' => 'integer',
            'last_synced_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(EmailProviderAllowance::class, 'provider_account_id');
    }

    public function usage(): HasMany
    {
        return $this->hasMany(EmailProviderUsage::class, 'provider_account_id');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(EmailProviderError::class, 'provider_account_id')->latest('occurred_at');
    }

    public function getSecrets(): array
    {
        if (empty($this->encrypted_credentials)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($this->encrypted_credentials);
            return json_decode($decrypted, true) ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function setSecrets(array $secrets): void
    {
        $this->encrypted_credentials = Crypt::encryptString(json_encode($secrets));
    }

    public function isPolicyCompliant(): bool
    {
        return $this->policy_status === 'VERIFIED_ALLOWED';
    }

    public function isReadyToSend(): bool
    {
        return $this->enabled
            && !$this->is_paused
            && $this->connection_status === 'CONNECTED'
            && $this->isPolicyCompliant();
    }

    public function providerDisplayName(): string
    {
        return match ($this->provider) {
            'gmail_api' => 'Gmail API',
            'google_apps_script' => 'Google Apps Script',
            'outlook_graph' => 'Outlook Graph',
            'zoho_mail' => 'Zoho Mail',
            'mailjet' => 'Mailjet',
            'smtp2go' => 'SMTP2GO',
            default => ucwords(str_replace('_', ' ', $this->provider)),
        };
    }

    public function providerIcon(): string
    {
        return match ($this->provider) {
            'gmail_api' => 'bi-google',
            'google_apps_script' => 'bi-file-earmark-code-fill',
            'outlook_graph' => 'bi-microsoft',
            'zoho_mail' => 'bi-envelope-at-fill',
            'mailjet' => 'bi-send-fill',
            'smtp2go' => 'bi-cloud-upload-fill',
            default => 'bi-envelope-check',
        };
    }
}

