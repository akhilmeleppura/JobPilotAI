<?php

namespace App\Modules\Candidate\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfile extends Model
{
    use HasFactory;

    protected $table = 'candidate_profiles';

    protected $fillable = [
        'user_id',
        'full_name',
        'headline',
        'primary_email',
        'phone',
        'location',
        'website_url',
        'github_url',
        'linkedin_url',
        'portfolio_url',
        'bio',
        'total_experience_years',
        'primary_skills',
        'spoken_languages',
    ];

    protected function casts(): array
    {
        return [
            'total_experience_years' => 'float',
            'primary_skills' => 'array',
            'spoken_languages' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

