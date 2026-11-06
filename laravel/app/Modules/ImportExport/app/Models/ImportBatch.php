<?php

namespace App\Modules\ImportExport\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    use HasFactory;

    protected $table = 'import_batches';

    protected $fillable = [
        'user_id',
        'filename',
        'file_type',
        'total_rows',
        'imported_count',
        'duplicates_count',
        'failed_count',
        'column_mapping',
        'status',
        'error_summary',
    ];

    protected function casts(): array
    {
        return [
            'column_mapping' => 'array',
            'total_rows' => 'integer',
            'imported_count' => 'integer',
            'duplicates_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

