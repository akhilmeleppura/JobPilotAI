<?php

namespace App\Modules\Resume\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumeVersion extends Model
{
    use HasFactory;

    protected $table = 'resume_versions';

    protected $fillable = [
        'resume_id',
        'version_number',
        'template_name',
        'source_type',
        'latex_source',
        'parsed_content',
        'pdf_path',
        'ats_score',
        'optimization_notes',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'ats_score' => 'integer',
            'parsed_content' => 'array',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }
}

