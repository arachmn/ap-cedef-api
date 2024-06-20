<?php

namespace App\Models\General;

use App\Models\AccApp\TaxAccounts;
use App\Models\AccApp\VendorAccounts;
use App\Models\EPRS\PurchaseOrders;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoices extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.invoices";
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

    public function taxAccounts()
    {
        return $this->belongsTo(TaxAccounts::class, 'ppn_account', 'liablyacccode');
    }

    public function purchaseOrders()
    {
        return $this->belongsTo(PurchaseOrders::class, 'po_number', 'po_no');
    }

    public function receiptNotes()
    {
        return $this->belongsTo(ReceiptNotes::class, 'rn_id', 'id');
    }

    public function vendorAccounts()
    {
        return $this->belongsTo(VendorAccounts::class, 'dpp_account', 'acccode');
    }

    public function pphAccounts()
    {
        return $this->belongsTo(VendorAccounts::class, 'pph_account', 'acccode');
    }

    public function users()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }

    public function searchData($code, $keyword)
    {
        try {
            $rows = $this->with('taxAccounts', 'pphAccounts')
                ->where('inv_number', 'LIKE', "%$keyword%")
                ->where('inv_pay_status', 0)
                ->where('inv_status', 2)
                ->where('vend_code', $code)
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

    public function getData($perPage, $status, $dateStart, $dateEnd)
    {
        try {
            $data = $this->with('vendors', 'users')
                ->when($status != 4, function ($query) use ($status) {
                    return $query->where('inv_status', $status);
                })
                ->when($dateStart && $dateEnd, function ($query) use ($dateStart, $dateEnd) {
                    return $query->whereBetween('inv_doc_date', [$dateStart, $dateEnd]);
                })
                ->orderByDesc('id')
                ->paginate($perPage);

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getAgingAll()
    {
        try {
            $data = Vendors::with(['invoices' => function ($query) {
                $query->select('vend_code', 'inv_number', 'inv_doc_date', 'inv_due_date', 'inv_not_payed')
                    ->where('inv_pay_status', 0)
                    ->where('inv_status', 2)
                    ->orderBy('inv_due_date', 'asc');
            }])->whereHas('invoices', function ($query) {
                $query->where('inv_pay_status', 0)
                    ->where('inv_status', 2);
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
                $beforeData = $vendor->invoices->filter(function ($invoice) use ($currentWeekStart) {
                    return Carbon::parse($invoice->inv_due_date)->lt($currentWeekStart);
                });

                $currentData = $vendor->invoices->filter(function ($invoice) use ($currentWeekStart, $currentWeekEnd) {
                    return Carbon::parse($invoice->inv_due_date)->between($currentWeekStart, $currentWeekEnd);
                });

                $after1Data = $vendor->invoices->filter(function ($invoice) use ($currentWeekEnd, $after1WeekEnd) {
                    return Carbon::parse($invoice->inv_due_date)->between($currentWeekEnd->copy()->addDay(), $after1WeekEnd);
                });

                $after2Data = $vendor->invoices->filter(function ($invoice) use ($after1WeekEnd, $after2WeekEnd) {
                    return Carbon::parse($invoice->inv_due_date)->between($after1WeekEnd->copy()->addDay(), $after2WeekEnd);
                });

                $after3Data = $vendor->invoices->filter(function ($invoice) use ($after2WeekEnd, $after3WeekEnd) {
                    return Carbon::parse($invoice->inv_due_date)->between($after2WeekEnd->copy()->addDay(), $after3WeekEnd);
                });

                $after4Data = $vendor->invoices->filter(function ($invoice) use ($after3WeekEnd) {
                    return Carbon::parse($invoice->inv_due_date)->gt($after3WeekEnd);
                });

                $beforeTotal += $beforeData->sum('inv_not_payed');
                $currentTotal += $currentData->sum('inv_not_payed');
                $after1Total += $after1Data->sum('inv_not_payed');
                $after2Total += $after2Data->sum('inv_not_payed');
                $after3Total += $after3Data->sum('inv_not_payed');
                $after4Total += $after4Data->sum('inv_not_payed');
                $totalData += $vendor->invoices->sum('inv_not_payed');

                return [
                    'vendor' => [
                        'id' => $vendor->id,
                        'vend_code' => $vendor->vend_code,
                        'vend_name' => $vendor->vend_name,
                        'before' => $beforeData->sum('inv_not_payed'),
                        'current' => $currentData->sum('inv_not_payed'),
                        'after1' => $after1Data->sum('inv_not_payed'),
                        'after2' => $after2Data->sum('inv_not_payed'),
                        'after3' => $after3Data->sum('inv_not_payed'),
                        'after4' => $after4Data->sum('inv_not_payed'),
                        'total' => $vendor->invoices->sum('inv_not_payed'),
                        'invoices' => [
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
                ->with(['invoices' => function ($query) {
                    $query->select('vend_code', 'inv_number', 'inv_doc_date', 'inv_due_date', 'inv_not_payed', 'ppn_type', 'pph_type', 'pph_account', 'ppn_amount', 'pph_amount', 'dpp_amount')
                        ->where('inv_pay_status', 0)
                        ->where('inv_status', 2)
                        ->orderBy('inv_due_date', 'asc');
                }, 'invoices.pphAccounts', 'vendorAccounts'])
                ->first();
            $ppn = TaxAccounts::all();

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

            $beforeData = $vendor->invoices->filter(function ($invoice) use ($currentWeekStart) {
                return Carbon::parse($invoice->inv_due_date)->lt($currentWeekStart);
            });

            $currentData = $vendor->invoices->filter(function ($invoice) use ($currentWeekStart, $currentWeekEnd) {
                return Carbon::parse($invoice->inv_due_date)->between($currentWeekStart, $currentWeekEnd);
            });

            $after1Data = $vendor->invoices->filter(function ($invoice) use ($currentWeekEnd, $after1WeekEnd) {
                return Carbon::parse($invoice->inv_due_date)->between($currentWeekEnd->copy()->addDay(), $after1WeekEnd);
            });

            $after2Data = $vendor->invoices->filter(function ($invoice) use ($after1WeekEnd, $after2WeekEnd) {
                return Carbon::parse($invoice->inv_due_date)->between($after1WeekEnd->copy()->addDay(), $after2WeekEnd);
            });

            $after3Data = $vendor->invoices->filter(function ($invoice) use ($after2WeekEnd, $after3WeekEnd) {
                return Carbon::parse($invoice->inv_due_date)->between($after2WeekEnd->copy()->addDay(), $after3WeekEnd);
            });

            $after4Data = $vendor->invoices->filter(function ($invoice) use ($after3WeekEnd) {
                return Carbon::parse($invoice->inv_due_date)->gt($after3WeekEnd);
            });

            $beforeTotal = $beforeData->sum('inv_not_payed');
            $currentTotal = $currentData->sum('inv_not_payed');
            $after1Total = $after1Data->sum('inv_not_payed');
            $after2Total = $after2Data->sum('inv_not_payed');
            $after3Total = $after3Data->sum('inv_not_payed');
            $after4Total = $after4Data->sum('inv_not_payed');
            $totalData = $vendor->invoices->sum('inv_not_payed');

            $vendorData = [
                'id' => $vendor->id,
                'vend_code' => $vendor->vend_code,
                'vend_name' => $vendor->vend_name,
                'vend_name' => $vendor->vend_name,
                'account' => $vendor->account,
                'dpp' => $vendor->vendorAccounts,
                'before' => $beforeTotal,
                'current' => $currentTotal,
                'after1' => $after1Total,
                'after2' => $after2Total,
                'after3' => $after3Total,
                'after4' => $after4Total,
                'total' => $totalData,
                'ppn' => $ppn,
                'invoices' => [
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
