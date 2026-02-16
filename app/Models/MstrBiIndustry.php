<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstrBiIndustry extends Model
{
    protected $table = 'mstr_bi_industries';
    protected $primaryKey = 'bi_code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['bi_code','description'];
}
