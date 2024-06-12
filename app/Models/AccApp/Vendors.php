<?php

namespace App\Models\AccApp;

use Illuminate\Database\Eloquent\Model;

class Vendors extends Model
{
    protected $connection = "connection_third";
    protected $table = "accappcdf.ap_vendor";


    public function searchData($keyword)
    {
        try {
            $rows = $this->select('vendcode', 'Vendname')
                ->where('vendname', 'LIKE', "%$keyword%")
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
