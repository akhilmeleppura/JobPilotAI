<?php

namespace App\Modules\Applications\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationRegistry extends Model
{
    use HasFactory;

    protected $table = 'application_registry';

    protected $fillable = [
        'user_id',
        'canonical_fingerprint',
        'company_name_normalized',
        'job_title_normalized',
        'location_normalized',
        'external_requisition_id',
        'source_url_hash',
        'first_discovered_at',
        'last_applied_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'first_discovered_at' => 'datetime',
            'last_applied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function identityKeys(): HasMany
    {
        return $this->hasMany(ApplicationIdentityKey::class, 'registry_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'registry_id');
    }
}

