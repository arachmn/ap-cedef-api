<?php

namespace App\Models\General;

use App\Models\EPRS\PurchaseOrders;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrders extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.delivery_orders";
    protected $guarded = ['id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function vendors()
    {
        return $this->belongsTo(Vendors::class, 'vend_code', 'vend_code');
    }

    public function purchaseOrders()
    {
        return $this->belongsTo(PurchaseOrders::class, 'po_number', 'po_no');
    }

    public function users()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }

    public function getData($perPage, $status, $dept, $dateStart, $dateEnd)
    {
        try {
            $data = $this->with('vendors', 'users')
                ->when($status != 5, function ($query) use ($status) {
                    return $query->where('do_status', $status);
                })
                ->when($dateStart && $dateEnd, function ($query) use ($dateStart, $dateEnd) {
                    return $query->whereBetween('do_date', [$dateStart, $dateEnd]);
                })
                ->where('dep_code', $dept)
                ->orderByDesc('id')
                ->paginate($perPage);

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function searchData($code, $dep, $keyword)
    {
        try {
            $rows = $this->with('purchaseOrders')
                ->whereHas('purchaseOrders')
                ->where('do_number', 'LIKE', "%$keyword%")
                ->where('do_status', 2)
                ->where('vend_code', $code)
                ->where('dep_code', $dep)
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
