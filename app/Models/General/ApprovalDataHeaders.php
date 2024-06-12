<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalDataHeaders extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.approval_data_headers";
    protected $guarded = ['id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function approvals()
    {
        return $this->hasMany(Approvals::class, 'apvdh_code', 'apvdh_code');
    }

    public function approvalHeaders()
    {
        return $this->belongsTo(ApprovalHeaders::class, 'apvh_code', 'apvh_code');
    }
}
