<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstrCimbSector extends Model
{
    protected $table = 'mstr_cimb_sectors';
    protected $primaryKey = 'sectoral_code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['sectoral_code','description'];
}
