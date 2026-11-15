<?php

namespace App\Modules\Outlook\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlookMessage extends Model
{
    use HasFactory;

    protected $table = 'outlook_messages';

    protected $fillable = [
        'user_id',
        'folder',
        'is_focused',
        'is_read',
        'is_flagged',
        'is_pinned',
        'sender_name',
        'sender_email',
        'recipient_name',
        'recipient_email',
        'subject',
        'snippet',
        'body_html',
        'category',
        'has_attachments',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'is_focused' => 'boolean',
            'is_read' => 'boolean',
            'is_flagged' => 'boolean',
            'is_pinned' => 'boolean',
            'has_attachments' => 'boolean',
            'received_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
