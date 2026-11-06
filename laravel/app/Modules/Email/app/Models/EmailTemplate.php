<?php

namespace App\Modules\Email\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $table = 'email_templates';

    protected $fillable = [
        'user_id',
        'category',
        'name',
        'slug',
        'subject_template',
        'body_template',
        'default_cc',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'default_cc' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeDefaultTemplate($query)
    {
        return $query->where('is_default', true);
    }
}

