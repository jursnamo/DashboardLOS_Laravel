<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanCovenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'covenant_phase',
        'covenant_text',
        'is_mandatory',
        'status',
        'due_date',
        'fulfilled_at',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'due_date' => 'date',
        'fulfilled_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }
}
