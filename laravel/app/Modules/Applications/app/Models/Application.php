<?php

namespace App\Modules\Applications\app\Models;

use App\Models\User;
use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $table = 'applications';

    protected $fillable = [
        'user_id',
        'registry_id',
        'job_vacancy_id',
        'role_profile_id',
        'resume_id',
        'resume_version_id',
        'applied_via',
        'discovered_from',
        'email_template_id',
        'email_account_id',
        'application_status',
        'email_delivery_status',
        'notes',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registry(): BelongsTo
    {
        return $this->belongsTo(ApplicationRegistry::class, 'registry_id');
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function roleProfile(): BelongsTo
    {
        return $this->belongsTo(RoleProfile::class, 'role_profile_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class, 'application_id')->orderByDesc('created_at');
    }
}

