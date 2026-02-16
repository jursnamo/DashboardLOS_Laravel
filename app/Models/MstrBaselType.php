<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstrBaselType extends Model
{
    protected $table = 'mstr_basel_types';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id','type_name'];
}
