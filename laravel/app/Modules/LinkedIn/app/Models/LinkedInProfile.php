<?php

namespace App\Modules\LinkedIn\app\Models;

use App\Models\User;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkedInProfile extends Model
{
    use HasFactory;

    protected $table = 'linkedin_profiles';

    protected $fillable = [
        'user_id',
        'role_profile_id',
        'current_headline',
        'current_about',
        'current_experience',
        'optimized_headline',
        'optimized_about',
        'optimized_experience',
        'headline_variations',
        'recruiter_score',
        'missing_keywords',
        'optimization_notes',
    ];

    protected function casts(): array
    {
        return [
            'headline_variations' => 'array',
            'missing_keywords' => 'array',
            'recruiter_score' => 'integer',
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
}

