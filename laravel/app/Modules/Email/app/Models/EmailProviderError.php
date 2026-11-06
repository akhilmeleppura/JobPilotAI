<?php

namespace App\Modules\Email\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailProviderError extends Model
{
    use HasFactory;

    protected $table = 'email_provider_errors';

    protected $fillable = [
        'provider_account_id',
        'error_code',
        'error_message',
        'occurred_at',
        'retry_status',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailProviderAccount::class, 'provider_account_id');
    }
}

