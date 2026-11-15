<?php

namespace App\Modules\Outlook\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlookEvent extends Model
{
    use HasFactory;

    protected $table = 'outlook_events';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location',
        'meeting_link',
        'start_time',
        'end_time',
        'is_all_day',
        'category',
        'color',
        'company_name',
        'interviewer_name',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_all_day' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
