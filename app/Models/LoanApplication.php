<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class LoanApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_number',
        'cif_number',
        'customer_name',
        'division',
        'segment',
        'loan_type',
        'apk_type',
        'purpose',
        'plafond_amount',
        'tenor_months',
        'bwmk_type',
        'rm_name',
        'branch_name',
        'current_stage',
        'ideb_result_status',
        'ideb_requested_at',
        'total_collateral_value',
        'total_liquidation_value',
        'expected_disbursement_date',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'created_by',
        'approved_by',
        'rejection_notes',
        'business_snapshot',
    ];

    protected $casts = [
        'plafond_amount' => 'decimal:2',
        'total_collateral_value' => 'decimal:2',
        'total_liquidation_value' => 'decimal:2',
        'ideb_requested_at' => 'datetime',
        'expected_disbursement_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'business_snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $application) {
            if (!$application->application_number) {
                $application->application_number = self::generateApplicationNumber();
            }
        });
    }

    public static function generateApplicationNumber(): string
    {
        $today = Carbon::now()->format('Ymd');
        $seq = self::query()->whereDate('created_at', Carbon::today())->count() + 1;

        return sprintf('LOS-%s-%04d', $today, $seq);
    }

    public function collaterals(): HasMany
    {
        return $this->hasMany(LoanCollateral::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LoanDocument::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LoanApproval::class)->orderBy('sequence_no');
    }

    public function covenants(): HasMany
    {
        return $this->hasMany(LoanCovenant::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(LoanActivityLog::class)->latest('created_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recalculateCollateralTotals(): void
    {
        $this->forceFill([
            'total_collateral_value' => (float) $this->collaterals()->sum('appraisal_value'),
            'total_liquidation_value' => (float) $this->collaterals()->sum('liquidation_value'),
        ])->save();
    }
}
