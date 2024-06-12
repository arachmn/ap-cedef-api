<?php

namespace App\Models\AccApp;

use Illuminate\Database\Eloquent\Model;

class VendorAccounts extends Model
{
    protected $connection = "connection_third";
    protected $table = "accappcdf.gl_account";

    public function searchData($keyword)
    {
        try {
            $rows = $this->selectRaw("acccode, id, accdesc, CONCAT(acccode, ' | ', accdesc) as acc_desc")
                ->where('acccode', 'LIKE', "%$keyword%")
                ->orWhere('accdesc', 'LIKE', "%$keyword%")
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
