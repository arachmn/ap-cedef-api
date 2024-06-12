<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalUsers extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.approval_users";
    protected $guarded = ['id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function users()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
}
