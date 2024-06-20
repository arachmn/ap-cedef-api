<?php

namespace App\Models\General;

use App\Models\AccApp\BankAccounts;
use App\Models\AccApp\TaxAccounts;
use App\Models\AccApp\VendorAccounts;
use Carbon\Carbon;
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

    public function getDataApprove($perPage, $status, $id)
    {
        try {
            $data = $this->with([
                'vendors',
                'taxAccounts',
                'pphAccounts',
                'vendorAccounts',
                'approvalDataHeaders.approvals' => function ($query) use ($id) {
                    $query->where('user_id', $id);
                },
                'users'
            ])
                ->whereHas('approvalDataHeaders.approvals', function ($query) use ($id) {
                    $query->where('user_id', $id);
                })
                ->whereNotIn('pv_status', [1, 3]);

            $status != 7 ? $data->where('pv_status', $status) : $data;

            $data = $data->orderBy('pv_status', 'asc')
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

    public function getAgingAll()
    {
        try {
            $data = Vendors::with(['paymentVouchers' => function ($query) {
                $query->select('vend_code', 'pv_number', 'pv_doc_date', 'pv_due_date', 'pv_amount')
                    ->where('pv_status', 4)
                    ->orderBy('pv_due_date', 'asc');
            }])->whereHas('paymentVouchers', function ($query) {
                $query->where('pv_status', 4);
            })->get();

            $beforeTotal = 0;
            $currentTotal = 0;
            $after1Total = 0;
            $after2Total = 0;
            $after3Total = 0;
            $after4Total = 0;
            $totalData = 0;

            $today = Carbon::today();
            $currentWeekStart = $today->copy()->startOfWeek(Carbon::THURSDAY);
            $currentWeekEnd = $today->copy()->endOfWeek(Carbon::WEDNESDAY);
            $after1WeekEnd = $currentWeekEnd->copy()->addWeek();
            $after2WeekEnd = $after1WeekEnd->copy()->addWeek();
            $after3WeekEnd = $after2WeekEnd->copy()->addWeek();
            $after4WeekEnd = $after3WeekEnd->copy()->addWeek();

            $groupedData = $data->map(function ($vendor) use ($currentWeekStart, $currentWeekEnd, $after1WeekEnd, $after2WeekEnd, $after3WeekEnd, $after4WeekEnd, &$beforeTotal, &$currentTotal, &$after1Total, &$after2Total, &$after3Total, &$after4Total, &$totalData) {
                $beforeData = $vendor->paymentVouchers->filter(function ($pv) use ($currentWeekStart) {
                    return Carbon::parse($pv->pv_due_date)->lt($currentWeekStart);
                });

                $currentData = $vendor->paymentVouchers->filter(function ($pv) use ($currentWeekStart, $currentWeekEnd) {
                    return Carbon::parse($pv->pv_due_date)->between($currentWeekStart, $currentWeekEnd);
                });

                $after1Data = $vendor->paymentVouchers->filter(function ($pv) use ($currentWeekEnd, $after1WeekEnd) {
                    return Carbon::parse($pv->pv_due_date)->between($currentWeekEnd->copy()->addDay(), $after1WeekEnd);
                });

                $after2Data = $vendor->paymentVouchers->filter(function ($pv) use ($after1WeekEnd, $after2WeekEnd) {
                    return Carbon::parse($pv->pv_due_date)->between($after1WeekEnd->copy()->addDay(), $after2WeekEnd);
                });

                $after3Data = $vendor->paymentVouchers->filter(function ($pv) use ($after2WeekEnd, $after3WeekEnd) {
                    return Carbon::parse($pv->pv_due_date)->between($after2WeekEnd->copy()->addDay(), $after3WeekEnd);
                });

                $after4Data = $vendor->paymentVouchers->filter(function ($pv) use ($after3WeekEnd) {
                    return Carbon::parse($pv->pv_due_date)->gt($after3WeekEnd);
                });

                $beforeTotal += $beforeData->sum('pv_amount');
                $currentTotal += $currentData->sum('pv_amount');
                $after1Total += $after1Data->sum('pv_amount');
                $after2Total += $after2Data->sum('pv_amount');
                $after3Total += $after3Data->sum('pv_amount');
                $after4Total += $after4Data->sum('pv_amount');
                $totalData += $vendor->paymentVouchers->sum('pv_amount');

                return [
                    'vendor' => [
                        'id' => $vendor->id,
                        'vend_code' => $vendor->vend_code,
                        'vend_name' => $vendor->vend_name,
                        'before' => $beforeData->sum('pv_amount'),
                        'current' => $currentData->sum('pv_amount'),
                        'after1' => $after1Data->sum('pv_amount'),
                        'after2' => $after2Data->sum('pv_amount'),
                        'after3' => $after3Data->sum('pv_amount'),
                        'after4' => $after4Data->sum('pv_amount'),
                        'total' => $vendor->paymentVouchers->sum('pv_amount'),
                        'paymentVouchers' => [
                            'before' => $beforeData->values()->all(),
                            'current' => $currentData->values()->all(),
                            'after1' => $after1Data->values()->all(),
                            'after2' => $after2Data->values()->all(),
                            'after3' => $after3Data->values()->all(),
                            'after4' => $after4Data->values()->all()
                        ],
                    ]
                ];
            });

            $sortedGroupedData = $groupedData->sortByDesc(function ($vendor) {
                return $vendor['vendor']['total'];
            })->values()->all();

            return [
                'vendors' => $sortedGroupedData,
                'before' => $beforeTotal,
                'current' => $currentTotal,
                'after1' => $after1Total,
                'after2' => $after2Total,
                'after3' => $after3Total,
                'after4' => $after4Total,
                'total' => $totalData
            ];
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function getAgingDetail($code)
    {
        try {
            $vendor = Vendors::where('vend_code', $code)
                ->with(['paymentVouchers' => function ($query) {
                    $query->select('id', 'vend_code', 'pv_number', 'pv_doc_date', 'pv_due_date', 'pv_amount')
                        ->where('pv_status', 4)
                        ->orderBy('pv_due_date', 'asc');
                },])
                ->first();

            if (!$vendor) {
                return 'Vendor not found';
            }

            $beforeTotal = 0;
            $currentTotal = 0;
            $after1Total = 0;
            $after2Total = 0;
            $after3Total = 0;
            $after4Total = 0;
            $totalData = 0;

            $today = Carbon::today();
            $currentWeekStart = $today->copy()->startOfWeek(Carbon::THURSDAY);
            $currentWeekEnd = $today->copy()->endOfWeek(Carbon::WEDNESDAY);
            $after1WeekEnd = $currentWeekEnd->copy()->addWeek();
            $after2WeekEnd = $after1WeekEnd->copy()->addWeek();
            $after3WeekEnd = $after2WeekEnd->copy()->addWeek();
            $after4WeekEnd = $after3WeekEnd->copy()->addWeek();

            $beforeData = $vendor->paymentVouchers->filter(function ($pv) use ($currentWeekStart) {
                return Carbon::parse($pv->pv_due_date)->lt($currentWeekStart);
            });

            $currentData = $vendor->paymentVouchers->filter(function ($pv) use ($currentWeekStart, $currentWeekEnd) {
                return Carbon::parse($pv->pv_due_date)->between($currentWeekStart, $currentWeekEnd);
            });

            $after1Data = $vendor->paymentVouchers->filter(function ($pv) use ($currentWeekEnd, $after1WeekEnd) {
                return Carbon::parse($pv->pv_due_date)->between($currentWeekEnd->copy()->addDay(), $after1WeekEnd);
            });

            $after2Data = $vendor->paymentVouchers->filter(function ($pv) use ($after1WeekEnd, $after2WeekEnd) {
                return Carbon::parse($pv->pv_due_date)->between($after1WeekEnd->copy()->addDay(), $after2WeekEnd);
            });

            $after3Data = $vendor->paymentVouchers->filter(function ($pv) use ($after2WeekEnd, $after3WeekEnd) {
                return Carbon::parse($pv->pv_due_date)->between($after2WeekEnd->copy()->addDay(), $after3WeekEnd);
            });

            $after4Data = $vendor->paymentVouchers->filter(function ($pv) use ($after3WeekEnd) {
                return Carbon::parse($pv->pv_due_date)->gt($after3WeekEnd);
            });

            $beforeTotal = $beforeData->sum('pv_amount');
            $currentTotal = $currentData->sum('pv_amount');
            $after1Total = $after1Data->sum('pv_amount');
            $after2Total = $after2Data->sum('pv_amount');
            $after3Total = $after3Data->sum('pv_amount');
            $after4Total = $after4Data->sum('pv_amount');
            $totalData = $vendor->paymentVouchers->sum('pv_amount');

            $vendorData = [
                'id' => $vendor->id,
                'vend_code' => $vendor->vend_code,
                'vend_name' => $vendor->vend_name,
                'vend_name' => $vendor->vend_name,
                'before' => $beforeTotal,
                'current' => $currentTotal,
                'after1' => $after1Total,
                'after2' => $after2Total,
                'after3' => $after3Total,
                'after4' => $after4Total,
                'total' => $totalData,
                'paymentVouchers' => [
                    'before' => $beforeData->values()->all(),
                    'current' => $currentData->values()->all(),
                    'after1' => $after1Data->values()->all(),
                    'after2' => $after2Data->values()->all(),
                    'after3' => $after3Data->values()->all(),
                    'after4' => $after4Data->values()->all()
                ]
            ];

            return $vendorData;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
