<?php

namespace App\Models\General;

use App\Models\AccApp\TaxAccounts;
use App\Models\AccApp\VendorAccounts;
use App\Models\EPRS\PurchaseOrders;
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

    public function getAgingSummary()
    {
        try {
            $currentDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime("last Thursday", strtotime($currentDate)));
            $endDate = date('Y-m-d', strtotime("next Wednesday", strtotime($startDate)));

            $futureEndDates = [];
            for ($i = 0; $i < 5; $i++) {
                $futureEndDates[] = $endDate;
                $endDate = date('Y-m-d', strtotime("+7 days", strtotime($endDate)));
            }
            $vendors = Vendors::with(['invoices' => function ($query) {
                $query->where('inv_status', 2)
                    ->where('inv_pay_status', 0);
            }])
                ->whereHas('invoices', function ($query) {
                    $query->where('inv_status', 2)
                        ->where('inv_pay_status', 0);
                })
                ->get();

            $data = [];
            $totalWeekly = [
                'total_week_1' => 0,
                'total_week_2' => 0,
                'total_week_3' => 0,
                'total_week_4' => 0,
                'total_week_5' => 0,
            ];
            foreach ($vendors as $vendor) {
                $vendorInvoiceCounts = [
                    'week_1' => 0,
                    'week_2' => 0,
                    'week_3' => 0,
                    'week_4' => 0,
                    'week_5' => 0,
                ];
                foreach ($vendor->invoices as $invoice) {
                    $dueDate = $invoice->inv_due_date;
                    $notPaidAmount = $invoice->inv_not_payed;
                    for ($i = 0; $i < 5; $i++) {
                        if ($dueDate <= $futureEndDates[$i]) {
                            $vendorInvoiceCounts["week_" . ($i + 1)] += $notPaidAmount;
                            $totalWeekly["total_week_" . ($i + 1)] += $notPaidAmount;
                            break;
                        }
                    }
                }
                $data[] = [
                    'vend_code' => $vendor->vend_code,
                    'vend_name' => $vendor->vend_name,
                    'weekly_invoice_data' => $vendorInvoiceCounts
                ];
            }
            $data = array_values($data);

            $finalOuput = [
                'vendors' => $data,
                'total_weekly' => $totalWeekly,
            ];
            return $finalOuput;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function getAgingDetail()
    {
        try {
            $currentDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime("last Thursday", strtotime($currentDate)));
            $endDate = date('Y-m-d', strtotime("next Wednesday", strtotime($startDate)));

            $futureEndDates = [];
            for ($i = 0; $i < 5; $i++) {
                $futureEndDates[] = $endDate;
                $endDate = date('Y-m-d', strtotime("+7 days", strtotime($endDate)));
            }

            $vendors = Vendors::with(['invoices' => function ($query) {
                $query->where('inv_status', 2)
                    ->where('inv_pay_status', 0);
            }])
                ->whereHas('invoices', function ($query) {
                    $query->where('inv_status', 2)
                        ->where('inv_pay_status', 0);
                })
                ->get();

            $vendorData = [];
            $totalWeekly = [
                'total_week_1' => 0,
                'total_week_2' => 0,
                'total_week_3' => 0,
                'total_week_4' => 0,
                'total_week_5' => 0,
            ];

            foreach ($vendors as $vendor) {
                $weeklyInvoices = [
                    'week_1' => ['total' => 0, 'invoices' => []],
                    'week_2' => ['total' => 0, 'invoices' => []],
                    'week_3' => ['total' => 0, 'invoices' => []],
                    'week_4' => ['total' => 0, 'invoices' => []],
                    'week_5' => ['total' => 0, 'invoices' => []],
                ];

                foreach ($vendor->invoices as $invoice) {
                    $dueDate = $invoice->inv_due_date;
                    $weekIndex = -1;
                    foreach ($futureEndDates as $index => $futureEndDate) {
                        if ($dueDate <= $futureEndDate) {
                            $weekIndex = $index;
                            break;
                        }
                    }

                    if ($weekIndex == -1) {
                        $weekIndex = 0;
                    }

                    $weekKey = 'week_' . ($weekIndex + 1);
                    $weeklyInvoices[$weekKey]['invoices'][] = $invoice;
                    $weeklyInvoices[$weekKey]['total'] += $invoice->inv_not_payed;
                    $totalWeekly['total_' . $weekKey] += $invoice->inv_not_payed;
                }

                foreach ($weeklyInvoices as $key => $invoices) {
                    if (empty($invoices['invoices'])) {
                        $weeklyInvoices[$key]['invoices'] = [];
                    }
                }

                $vendorData[] = [
                    'vendor' => [
                        'vend_name' => $vendor->vend_name,
                        'vend_code' => $vendor->vend_code,
                    ],
                    'weekly' => $weeklyInvoices
                ];
            }


            $finalOuput = [
                'vendors' => $vendorData,
                'total_weekly' => $totalWeekly,
            ];

            return $finalOuput;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }



    public function getAgingDetailVendor($code)
    {
        try {
            $currentDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime("last Thursday", strtotime($currentDate)));
            $endDate = date('Y-m-d', strtotime("next Wednesday", strtotime($startDate)));

            $futureEndDates = [];
            for ($i = 0; $i < 5; $i++) {
                $futureEndDates[] = $endDate;
                $endDate = date('Y-m-d', strtotime("+7 days", strtotime($endDate)));
            }

            $vendor = Vendors::with(['invoices' => function ($query) {
                $query->where('inv_status', 2)
                    ->where('inv_pay_status', 0);
            }])->where('vend_code', $code)->first();

            $weeklyInvoices = [
                'week_1' => ['total' => 0, 'invoices' => []],
                'week_2' => ['total' => 0, 'invoices' => []],
                'week_3' => ['total' => 0, 'invoices' => []],
                'week_4' => ['total' => 0, 'invoices' => []],
                'week_5' => ['total' => 0, 'invoices' => []],
            ];

            foreach ($vendor->invoices as $invoice) {
                $dueDate = $invoice->inv_due_date;
                $weekIndex = -1;
                foreach ($futureEndDates as $index => $futureEndDate) {
                    if ($dueDate <= $futureEndDate) {
                        $weekIndex = $index;
                        break;
                    }
                }

                if ($weekIndex == -1) {
                    $weekIndex = 0;
                }

                $weekKey = 'week_' . ($weekIndex + 1);
                $weeklyInvoices[$weekKey]['invoices'][] = $invoice;
                $weeklyInvoices[$weekKey]['total'] += $invoice->inv_not_payed;
            }

            foreach ($weeklyInvoices as $key => $invoices) {
                if (empty($invoices['invoices'])) {
                    $weeklyInvoices[$key]['invoices'] = [];
                }
            }

            $data = [
                'vendor' => [
                    'vend_name' => $vendor->vend_name,
                    'vend_code' => $vendor->vend_code,
                ],
                'weekly' => $weeklyInvoices
            ];
            return $data;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
