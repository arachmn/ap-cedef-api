<?php

namespace App\Models\General;

use App\Models\AccApp\BankAccounts;
use App\Models\AccApp\TaxAccounts;
use App\Models\AccApp\VendorAccounts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentVouchers extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.payment_vouchers";
    protected $guarded = ['id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function taxAccounts()
    {
        return $this->belongsTo(TaxAccounts::class, 'ppn_account', 'liablyacccode');
    }

    public function paymentVoucherRealizations()
    {
        return $this->belongsTo(PaymentVoucherRealizations::class, 'pv_number', 'pv_number');
    }

    public function paymentVoucherInvoices()
    {
        return $this->hasMany(PaymentVoucherInvoices::class, 'pv_id', 'id');
    }

    public function bankAccounts()
    {
        return $this->belongsTo(BankAccounts::class, 'bank_id', 'id');
    }

    public function vendorAccounts()
    {
        return $this->belongsTo(VendorAccounts::class, 'dpp_account', 'acccode');
    }

    public function pphAccounts()
    {
        return $this->belongsTo(VendorAccounts::class, 'pph_account', 'acccode');
    }

    public function vendors()
    {
        return $this->belongsTo(Vendors::class, 'vend_code', 'vend_code');
    }

    public function users()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }

    public function approvalDataHeaders()
    {
        return $this->belongsTo(ApprovalDataHeaders::class, 'apvdh_code', 'apvdh_code');
    }

    public function getData($perPage, $status, $dateStart, $dateEnd)
    {
        try {
            $data = $this->with('vendors', 'users')
                ->when($status != 7, function ($query) use ($status) {
                    return $query->where('pv_status', $status);
                })
                ->when($dateStart && $dateEnd, function ($query) use ($dateStart, $dateEnd) {
                    return $query->whereBetween('pv_doc_date', [$dateStart, $dateEnd]);
                })
                ->orderByDesc('id')
                ->paginate($perPage);

            return $data;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function getDataRealizations($perPage, $dateStart, $dateEnd)
    {
        try {
            $data = $this->with('vendors', 'users', 'paymentVoucherRealizations.users')
                ->when($dateStart && $dateEnd, function ($query) use ($dateStart, $dateEnd) {
                    return $query->whereBetween('pv_doc_date', [$dateStart, $dateEnd]);
                })
                ->where(function ($query) {
                    $query->where('pv_status', 4)
                        ->orWhere(function ($query) {
                            $query->where('pv_status', 6)
                                ->whereHas('paymentVoucherRealizations');
                        })
                        ->orWhere(function ($query) {
                            $query->where('pv_status', 3)
                                ->whereHas('paymentVoucherRealizations');
                        });
                })
                ->orderByDesc('id')
                ->paginate($perPage);

            return $data;
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
