<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'app_id',
        'segment',
        'purpose',
        'approved_limit',
        'branch_name',
        'booking_month',
        'start_date',
        'end_date',
        'complete_date',
        'tat_days',
        'status_flow',
        'row_order',
    ];

    protected $casts = [
        'approved_limit' => 'decimal:2',
        'tat_days' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'complete_date' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DashboardImportBatch::class, 'batch_id');
    }
}
