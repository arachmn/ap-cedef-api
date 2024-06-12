<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Users extends Model implements AuthenticatableContract, JWTSubject
{
    use SoftDeletes, Authenticatable, HasFactory;

    protected $connection = "connection_first";
    protected $table = "ap_general.users";
    protected $guarded = ['id'];
    protected $hidden = [
        'password',
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function roles()
    {
        return $this->belongsTo(Roles::class, 'role_id', 'id');
    }

    public function departements()
    {
        return $this->belongsTo(Departements::class, 'dep_code', 'dep_code');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
