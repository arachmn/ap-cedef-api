<?php

namespace App\Models\EPRS;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrders extends Model
{
    protected $connection = "connection_second";
    protected $table = "budget2024.print_po";

    public function requisitions()
    {
        return $this->belongsTo(Requisitions::class, 'pp_no', 'RequisitionNo');
    }

    public function searchData($keyword)
    {
        try {
            $rows = $this->selectRaw('pp_no, MAX(po_no) AS po_no, MAX(tgl_buat) AS tgl_buat')
                ->where('po_no', 'LIKE', "%$keyword%")
                ->groupBy('pp_no')
                ->orderByDesc('id')
                ->limit(20)
                ->get();

            if ($rows->isEmpty()) {
                return false;
            } else {
                return $rows;
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
