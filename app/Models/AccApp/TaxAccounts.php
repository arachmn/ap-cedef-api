<?php

namespace App\Models\AccApp;

use Illuminate\Database\Eloquent\Model;

class TaxAccounts extends Model
{
    protected $connection = "connection_third";
    protected $table = "accappcdf.op_tax";
}
