<?php

namespace App\Modules\Outlook\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlookTask extends Model
{
    use HasFactory;

    protected $table = 'outlook_tasks';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'is_completed',
        'is_important',
        'is_my_day',
        'due_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'is_important' => 'boolean',
            'is_my_day' => 'boolean',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
