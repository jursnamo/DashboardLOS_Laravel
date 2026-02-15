<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Login extends Model
{
    use HasFactory, HasRoles;

    protected $table = 'logins';

    protected $fillable = [
        'user_id',
        'email',
        'last_login_at',
        'last_login_ip',
        'login_count',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
