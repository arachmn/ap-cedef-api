<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptNotes extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.receipt_notes";
    protected $guarded = ['id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function deliveryOrders()
    {
        return $this->belongsTo(DeliveryOrders::class, 'do_id', 'id');
    }

    public function departements()
    {
        return $this->belongsTo(Departements::class, 'dep_code', 'dep_code');
    }

    public function users()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }

    public function receiptNoteTypes()
    {
        return $this->belongsTo(ReceiptNoteTypes::class, 'rnt_id', 'id');
    }

    public function approvalDataHeaders()
    {
        return $this->belongsTo(ApprovalDataHeaders::class, 'apvdh_code', 'apvdh_code');
    }

    public function getData($perPage, $status, $dept, $dateStart, $dateEnd)
    {
        try {
            $data = $this->with('deliveryOrders.vendors', 'receiptNoteTypes', 'users')
                ->when($status != 7, function ($query) use ($status) {
                    return $query->where('rn_status', $status);
                })
                ->when($dateStart && $dateEnd, function ($query) use ($dateStart, $dateEnd) {
                    return $query->whereBetween('rn_date', [$dateStart, $dateEnd]);
                })
                ->where('dep_code', $dept)
                ->orderByDesc('id')
                ->paginate($perPage);

            return $data;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function getDataApprove($perPage, $status, $dep, $id)
    {
        try {
            $data = $this->with([
                'deliveryOrders.vendors',
                'departements',
                'deliveryOrders.purchaseOrders',
                'approvalDataHeaders.approvals' => function ($query) use ($id) {
                    $query->where('user_id', $id);
                },
                'users'
            ])
                ->whereHas('approvalDataHeaders.approvals', function ($query) use ($id) {
                    $query->where('user_id', $id);
                })
                ->whereNotIn('rn_status', [1, 3]);

            $status != 7 ? $data->where('rn_status', $status) : $data;
            $dep ? $data->where('dep_code', $dep) : $data;

            $data = $data->orderBy('rn_status', 'asc')
                ->paginate($perPage);

            return $data;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }


    public function searchData($code, $keyword)
    {
        try {
            $rows = $this->with(['deliveryOrders' => function ($query) use ($code) {
                $query->where('vend_code', $code);
            }])
                ->whereHas('deliveryOrders', function ($query) use ($code) {
                    $query->where('vend_code', $code);
                })
                ->where('rn_number', 'LIKE', "%$keyword%")
                ->where('rn_status', 4)
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
