<?php

namespace App\Modules\Archives\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationArchive extends Model
{
    use HasFactory;

    protected $table = 'application_archives';

    protected $fillable = [
        'user_id',
        'archive_year',
        'archive_month',
        'records_count',
        'jsonl_filename',
        'jsonl_checksum_sha256',
        'file_size_bytes',
        'csv_filename',
        'csv_checksum_sha256',
        'gdrive_file_id',
        'gdrive_sheet_id',
        'status',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archive_year' => 'integer',
            'archive_month' => 'integer',
            'records_count' => 'integer',
            'file_size_bytes' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedPeriodAttribute(): string
    {
        return date('F Y', mktime(0, 0, 0, $this->archive_month, 1, $this->archive_year));
    }
}

