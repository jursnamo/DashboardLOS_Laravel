<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'sequence_no',
        'actor_type',
        'approver_role',
        'approver_name',
        'decision',
        'decision_at',
        'notes',
        'sla_hours',
        'assigned_user_id',
        'started_at',
        'due_at',
        'completed_at',
    ];

    protected $casts = [
        'decision_at' => 'datetime',
        'started_at' => 'datetime',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'sla_hours' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
