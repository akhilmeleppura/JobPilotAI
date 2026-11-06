<?php

namespace App\Modules\Applications\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationEvent extends Model
{
    use HasFactory;

    protected $table = 'application_events';

    protected $fillable = [
        'application_id',
        'event_type',
        'event_payload',
    ];

    protected function casts(): array
    {
        return [
            'event_payload' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }
}

