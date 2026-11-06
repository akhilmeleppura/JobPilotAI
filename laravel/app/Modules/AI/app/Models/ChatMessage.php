<?php

namespace App\Modules\AI\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_session_id',
        'sender',
        'message',
        'attachments',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }
}

