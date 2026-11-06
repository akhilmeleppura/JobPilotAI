<?php

namespace App\Modules\Email\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSwitchingSetting extends Model
{
    use HasFactory;

    protected $table = 'email_switching_settings';

    protected $fillable = [
        'user_id',
        'automatic_switching',
        'provider_mode',
        'allow_technical_failover',
        'allow_quota_switch',
        'free_only_mode',
    ];

    protected function casts(): array
    {
        return [
            'automatic_switching' => 'boolean',
            'allow_technical_failover' => 'boolean',
            'allow_quota_switch' => 'boolean',
            'free_only_mode' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

