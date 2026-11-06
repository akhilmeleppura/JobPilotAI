<?php

namespace App\Modules\ContentStudio\app\Models;

use App\Models\User;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPost extends Model
{
    use HasFactory;

    protected $table = 'content_posts';

    protected $fillable = [
        'user_id',
        'role_profile_id',
        'title',
        'topic',
        'category',
        'content',
        'image_prompt',
        'image_path',
        'image_provider',
        'scheduled_at',
        'published_at',
        'status',
        'metrics',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'metrics' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roleProfile(): BelongsTo
    {
        return $this->belongsTo(RoleProfile::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}

