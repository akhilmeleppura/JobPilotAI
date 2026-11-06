<?php

namespace App\Modules\Email\app\Models;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    protected $table = 'email_logs';

    protected $fillable = [
        'user_id',
        'application_id',
        'attempt_uuid',
        'idempotency_key',
        'email_account_id',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'subject',
        'body_html',
        'status',
        'provider_message_id',
        'error_message',
        'previous_provider',
        'switch_count',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'to_recipients' => 'array',
            'cc_recipients' => 'array',
            'bcc_recipients' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'email_account_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function getTotalRecipientsCountAttribute(): int
    {
        return count($this->to_recipients ?? []) + count($this->cc_recipients ?? []) + count($this->bcc_recipients ?? []);
    }
}

