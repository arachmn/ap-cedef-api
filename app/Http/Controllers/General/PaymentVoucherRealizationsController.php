<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\Invoices;
use App\Models\General\PaymentVoucherRealizations;
use App\Models\General\PaymentVouchers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentVoucherRealizationsController extends Controller
{

    protected $connFirst, $paymentVoucherRealizationsModel, $paymentVouchersModel;

    public function __construct()
    {
        $this->connFirst = DB::connection('connection_first');
        $this->paymentVoucherRealizationsModel = new PaymentVoucherRealizations();
        $this->paymentVouchersModel = new PaymentVouchers();
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('perPage', 50);
            $dateStart = $request->input('dateStart');
            $dateEnd = $request->input('dateEnd');

            $data = $this->paymentVouchersModel->getDataRealizations($perPage, $dateStart, $dateEnd);

            if ($data) {
                return response()->json([
                    "code" => 200,
                    "status" => true,
                    "data" => $data
                ], 200);
            } else {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "Not found.",
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function saveData(Request $request, $id): JsonResponse
    {
        try {

            $getPV = PaymentVouchers::where('id', $id)->first();

            if (!$getPV) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => "Not found."
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'pvr_number' => 'required|string',
                'pvr_status' => 'required|integer',
                'pvr_rel_date' => 'required|date',
                'pvr_due_date' => 'required|date',
                'pvr_note' => 'nullable|string',
                'user_id' => 'required|integer',
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }


            $validatedData = $validator->validated();

            $this->connFirst->beginTransaction();

            $validatedData['pvr_doc_date'] = date('Y-m-d');
            $validatedData['pv_number'] = $getPV->pv_number;

            $pvStatus = $validatedData['pvr_status'];

            PaymentVoucherRealizations::create($validatedData);

            $pviData = PaymentVouchers::with('paymentVoucherInvoices')->get();

            if ($pvStatus == 1) {
                $getPV->update(['pv_status' => 6]);

                foreach ($pviData as $item) {
                    $getInv = Invoices::where('inv_number', $item->inv_number)->where('inv_status', 2)->first();

                    if ($getInv) {
                        $invPayStatus = (intval($getInv->inv_not_payed) - intval($getPV->pv_amount) == 0) ? 1 : 0;

                        $getInv->update([
                            'inv_payed' =>  intval($getInv->inv_payed) + intval($getPV->pv_amount),
                            'inv_not_payed' => intval($getInv->inv_not_payed) - intval($getPV->pv_amount),
                            'inv_pay_status' => $invPayStatus,
                        ]);
                    }
                }
            }

            $this->connFirst->commit();

            return response()->json([
                "code" => 200,
                "status" => true
            ], 200);
        } catch (\Throwable $th) {
            $this->connFirst->rollBack();
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function cancelData(Request $request, $id): JsonResponse
    {
        try {

            $getPV = PaymentVouchers::where('id', $id)->first();

            if (!$getPV) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => "Not found."
                ], 404);
            }

            $pvrData = [
                'pv_number' => $getPV->pv_number,
                'pvr_status' => 2,
                'pvr_rel_date' => date('Y-m-d'),
                'pvr_doc_date' => date('Y-m-d'),
                'user_id' => $request->input('user'),
            ];

            $getPV->update(['pv_status' => 3]);

            PaymentVoucherRealizations::create($pvrData);

            $this->connFirst->commit();

            return response()->json([
                "code" => 200,
                "status" => true
            ], 200);
        } catch (\Throwable $th) {
            $this->connFirst->rollBack();
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
