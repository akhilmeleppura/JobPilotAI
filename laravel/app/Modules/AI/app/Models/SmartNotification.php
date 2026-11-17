<?php

namespace App\Modules\AI\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartNotification extends Model
{
    use HasFactory;

    protected $table = 'smart_notifications';

    protected $fillable = [
        'user_id',
        'type', // e.g. 'interview_alert', 'outreach_alert', 'system_alert', 'api_warning', 'task_reminder'
        'title',
        'message',
        'action_url',
        'action_label',
        'icon',
        'badge_color', // 'primary', 'success', 'warning', 'danger', 'info'
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
