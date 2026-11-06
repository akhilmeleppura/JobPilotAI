<?php

namespace App\Modules\Resume\app\Models;

use App\Models\User;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resume extends Model
{
    use HasFactory;

    protected $table = 'resumes';

    protected $fillable = [
        'user_id',
        'role_profile_id',
        'title',
        'slug',
        'current_version_id',
        'is_global_default',
        'target_role_name',
        'primary_skills',
    ];

    protected function casts(): array
    {
        return [
            'is_global_default' => 'boolean',
            'primary_skills' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roleProfile(): BelongsTo
    {
        return $this->belongsTo(RoleProfile::class, 'role_profile_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ResumeVersion::class, 'resume_id')->orderByDesc('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ResumeVersion::class, 'current_version_id');
    }

    public function scopeGlobalDefault($query)
    {
        return $query->where('is_global_default', true);
    }
}

