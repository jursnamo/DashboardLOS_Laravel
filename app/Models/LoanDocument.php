<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'document_name',
        'document_category',
        'is_required',
        'is_uploaded',
        'verification_status',
        'uploaded_at',
        'notes',
        'file_disk',
        'file_path',
        'original_filename',
        'file_mime',
        'file_size',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_uploaded' => 'boolean',
        'uploaded_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }
}
