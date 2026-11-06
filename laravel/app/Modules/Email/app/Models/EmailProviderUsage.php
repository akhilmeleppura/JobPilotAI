<?php

namespace App\Modules\Email\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailProviderUsage extends Model
{
    use HasFactory;

    protected $table = 'email_provider_usage';

    protected $fillable = [
        'provider_account_id',
        'message_id',
        'recipient_count',
        'sent_at',
        'provider_response',
        'delivery_status',
    ];

    protected function casts(): array
    {
        return [
            'recipient_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailProviderAccount::class, 'provider_account_id');
    }
}

