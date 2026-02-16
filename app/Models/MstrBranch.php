<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstrBranch extends Model
{
    protected $table = 'mstr_branches';
    protected $primaryKey = 'branch_id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['branch_name','area_region'];
}
