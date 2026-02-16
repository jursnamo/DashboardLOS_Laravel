<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstrEconomySector extends Model
{
    protected $table = 'mstr_economy_sectors';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id','sector_name'];
}
