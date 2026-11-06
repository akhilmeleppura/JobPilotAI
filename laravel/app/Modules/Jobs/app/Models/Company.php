<?php

namespace App\Modules\Jobs\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'normalized_name',
        'domain',
        'website',
        'career_page_url',
        'location',
    ];

    protected static function booted(): void
    {
        static::creating(function ($company) {
            if (empty($company->normalized_name) && !empty($company->name)) {
                $company->normalized_name = strtolower(trim($company->name));
            }
        });
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(JobVacancy::class);
    }
}

