<?php

namespace App\Models\AccApp;

use Illuminate\Database\Eloquent\Model;

class BankAccounts extends Model
{
    protected $connection = "connection_third";
    protected $table = "accappcdf.cb_bank";

    public function searchData($keyword)
    {
        try {
            $rows = $this->selectRaw("*, CONCAT(bankcode, ' | ', bankname) as bank_n_code")
                ->where('bankcode', 'LIKE', "%$keyword%")
                ->orWhere('bankname', 'LIKE', "%$keyword%")
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
