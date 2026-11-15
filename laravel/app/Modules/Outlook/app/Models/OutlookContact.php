<?php

namespace App\Modules\Outlook\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlookContact extends Model
{
    use HasFactory;

    protected $table = 'outlook_contacts';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'company',
        'job_title',
        'phone',
        'linkedin_url',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
