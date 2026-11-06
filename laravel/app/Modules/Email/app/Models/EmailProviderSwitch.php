<?php

namespace App\Modules\Email\app\Models;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailProviderSwitch extends Model
{
    use HasFactory;

    protected $table = 'email_provider_switches';

    protected $fillable = [
        'user_id',
        'application_id',
        'email_log_id',
        'email_attempt_id',
        'previous_provider',
        'new_provider',
        'reason',
        'quota_source',
        'provider_response',
        'policy_status',
        'result',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class, 'email_log_id');
    }
}

