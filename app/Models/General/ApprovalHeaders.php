<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalHeaders extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.approval_headers";
    protected $guarded = ['id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function approvalUsers()
    {
        return $this->hasMany(ApprovalUsers::class, 'apvh_code', 'apvh_code');
    }

    public function users()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }

    public function departements()
    {
        return $this->belongsTo(Departements::class, 'dep_code', 'dep_code');
    }

    public function getData($perPage, $status)
    {
        try {
            $data = ApprovalHeaders::with('users', 'departements');
            $status != 2 ? $data->where('apvh_status', $status) : $data;
            $data = $data->orderByDesc('apvh_status')->paginate($perPage);
            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getDetail($id)
    {
        try {
            $data = ApprovalHeaders::with('users', 'approvalUsers.users', 'departements')->find($id);
            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
