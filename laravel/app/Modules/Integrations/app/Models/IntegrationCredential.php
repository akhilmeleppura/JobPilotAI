<?php

namespace App\Modules\Integrations\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class IntegrationCredential extends Model
{
    use HasFactory;

    protected $table = 'integration_credentials';

    protected $fillable = [
        'user_id',
        'provider_group',
        'provider_key',
        'provider_name',
        'encrypted_secrets',
        'settings',
        'is_enabled',
        'last_health_check_at',
        'health_status',
        'health_message',
        'requests_today',
        'quota_limit_daily',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_enabled' => 'boolean',
            'requests_today' => 'integer',
            'quota_limit_daily' => 'integer',
            'last_health_check_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retrieve decrypted secrets payload.
     */
    public function getSecrets(): array
    {
        if (empty($this->encrypted_secrets)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($this->encrypted_secrets);
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
        $this->encrypted_secrets = Crypt::encryptString(json_encode($secrets));
    }

    /**
     * Get a safely masked representation of a secret for display.
     */
    public function getMaskedSecret(string $key): string
    {
        $secrets = $this->getSecrets();
        $val = $secrets[$key] ?? '';

        if (empty($val)) {
            return 'Not Configured';
        }

        $length = strlen($val);
        if ($length <= 8) {
            return str_repeat('•', $length);
        }

        return substr($val, 0, 4) . str_repeat('•', max(4, $length - 8)) . substr($val, -4);
    }
}

