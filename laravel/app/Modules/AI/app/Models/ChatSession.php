<?php

namespace App\Modules\AI\app\Models;

use App\Models\User;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use HasFactory;

    protected $table = 'chat_sessions';

    protected $fillable = [
        'user_id',
        'title',
        'active_role_profile_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roleProfile(): BelongsTo
    {
        return $this->belongsTo(RoleProfile::class, 'active_role_profile_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_session_id')->orderBy('created_at');
    }
}

