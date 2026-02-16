<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanCollateral extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'collateral_type',
        'collateral_subtype',
        'description',
        'appraisal_value',
        'liquidation_value',
        'ownership_name',
        'document_number',
        'is_primary',
    ];

    protected $casts = [
        'appraisal_value' => 'decimal:2',
        'liquidation_value' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }
}
