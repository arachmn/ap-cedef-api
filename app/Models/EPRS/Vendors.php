<?php

namespace App\Models\EPRS;

use Illuminate\Database\Eloquent\Model;

class Vendors extends Model
{
    protected $connection = "connection_second";
    protected $table = "budget2024.vendor";

    public function searchData($keyword)
    {
        try {
            $rows = $this->selectRaw("id, CONCAT(id, ' | ', VendName) as id_vendname")
                ->where('VendName', 'LIKE', "%$keyword%")
                ->orWhere('id', 'LIKE', "%$keyword%")
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
