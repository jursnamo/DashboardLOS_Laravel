<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardDatamart extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'source_batch_id',
        'status',
        'records_count',
        'payload_json',
    ];

    protected $casts = [
        'records_count' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DashboardImportBatch::class, 'batch_id');
    }
}
