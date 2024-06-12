<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentVoucherInvoices extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.payment_voucher_invoices";
    protected $guarded = ['id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function invoices()
    {
        return $this->belongsTo(Invoices::class, 'inv_number', 'inv_number');
    }
}
