<?php

namespace App\Models\General;

use App\Models\AccApp\VendorAccounts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendors extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.vendors";
    protected $guarded = ['id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function invoices()
    {
        return $this->hasMany(Invoices::class, 'vend_code', 'vend_code');
    }

    public function vendorAccounts()
    {
        return $this->belongsTo(VendorAccounts::class, 'account', 'acccode');
    }

    public function searchData($keyword)
    {
        try {
            $rows = $this->with('vendorAccounts')->selectRaw("*, CONCAT(vend_code, ' | ', vend_name) as id_vendname, account")
                ->where('vend_name', 'LIKE', "%$keyword%")
                ->orWhere('vend_code', 'LIKE', "%$keyword%")
                ->limit(20)
                ->get();

            if ($rows->isEmpty()) {
                return false;
            } else {
                return $rows;
            }
        } catch (\Throwable $th) {
            return false;
        }
    }
}
