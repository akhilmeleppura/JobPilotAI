<?php

namespace App\Modules\Applications\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationIdentityKey extends Model
{
    use HasFactory;

    protected $table = 'application_identity_keys';

    protected $fillable = [
        'registry_id',
        'key_type',
        'key_value',
    ];

    public function registry(): BelongsTo
    {
        return $this->belongsTo(ApplicationRegistry::class, 'registry_id');
    }
}

