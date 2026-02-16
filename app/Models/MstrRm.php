<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstrRm extends Model
{
    protected $table = 'mstr_rms';
    protected $primaryKey = 'rm_code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['rm_code','rm_name','unit_name'];
    public $timestamps = true;
}
