<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanApprovalMatrix extends Model
{
    use HasFactory;

    protected $fillable = [
        'division',
        'segment',
        'bwmk_type',
        'actor_type',
        'sequence_no',
        'role_name',
        'sla_hours',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sla_hours' => 'integer',
        'sequence_no' => 'integer',
    ];
}
