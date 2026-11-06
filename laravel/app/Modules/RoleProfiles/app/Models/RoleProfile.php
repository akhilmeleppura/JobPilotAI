<?php

namespace App\Modules\RoleProfiles\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleProfile extends Model
{
    use HasFactory;

    protected $table = 'role_profiles';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'target_titles',
        'technical_skills',
        'secondary_skills',
        'experience_years',
        'summary',
        'location_preferences',
        'work_modes',
        'salary_min',
        'salary_max',
        'currency',
        'search_keywords',
        'default_resume_id',
        'email_signature',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'target_titles' => 'array',
            'technical_skills' => 'array',
            'secondary_skills' => 'array',
            'location_preferences' => 'array',
            'work_modes' => 'array',
            'search_keywords' => 'array',
            'experience_years' => 'float',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefaultRole($query)
    {
        return $query->where('is_default', true);
    }
}

