<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'source_type',
        'calculation_mode',
        'status',
        'total_rows',
        'imported_rows',
        'error_message',
        'started_at',
        'completed_at',
        'imported_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'imported_at' => 'datetime',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(DashboardRecord::class, 'batch_id');
    }
}
