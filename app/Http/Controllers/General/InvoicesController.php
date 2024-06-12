<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\Invoices;
use App\Models\EPRS\Vendors as VendorsEPRS;
use App\Models\General\ReceiptNotes;
use App\Models\General\Vendors as VendorsGeneral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InvoicesController extends Controller
{
    protected $connFirst, $invoicesModel;

    public function __construct()
    {
        $this->connFirst = DB::connection('connection_first');
        $this->invoicesModel = new Invoices();
    }

    public function searchData(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword');
            $code = $request->input('code');

            $data = $this->invoicesModel->searchData($code, $keyword);

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

    public function getDetail($id): JsonResponse
    {
        try {
            $data = Invoices::with('vendors', 'users', 'taxAccounts', 'vendorAccounts', 'pphAccounts')->where('id', $id)->first();
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

    public function getData(Request $request): JsonResponse
    {
        try {

            $perPage = $request->input('perPage', 50);
            $status = $request->input('status', 4);
            $dateStart = $request->input('dateStart');
            $dateEnd = $request->input('dateEnd');

            $data = $this->invoicesModel->getData($perPage, $status, $dateStart, $dateEnd);

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

    public function getAging(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'summary');
            $dateStart = $request->input('cutOffDoc');
            $dateEnd = $request->input('cutOffDue');

            if ($type == 'summary') {
                $data = $this->invoicesModel->getAgingSummary($dateStart, $dateEnd);
            } elseif ($type == 'detail') {
                $data = $this->invoicesModel->getAgingDetail($dateStart, $dateEnd);
            }

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

    public function getAgingVendor(Request $request, $id): JsonResponse
    {
        try {

            $data = $this->invoicesModel->getAgingDetailVendor($id);

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

    public function saveData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'vend_code' => 'required|integer',
                'inv_type' => 'required|integer',
                'po_number' => 'nullable|string',
                'rn_id' => 'nullable|integer',
                'rn_number' => 'nullable|string',
                'ref_number' => 'nullable|string',
                'inv_number' => 'required|string',
                'inv_status' => 'required|integer',
                'inv_receipt_date' => 'required|date',
                'inv_doc_date' => 'required|date',
                'inv_due_date' => 'required|date',
                'ppn_type' => 'required|integer',
                'ppn_number' => 'nullable|string',
                'ppn_amount' => 'required|numeric',
                'ppn_account' => 'required|string',
                'pph_type' => 'required|integer',
                'pph_number' => 'nullable|string',
                'pph_amount' => 'required|numeric',
                'pph_account' => 'nullable|string',
                'dpp_amount' => 'required|numeric',
                'dpp_account' => 'required|string',
                'inv_amount' => 'required|numeric',
                'inv_not_payed' => 'required|numeric',
                'inv_note' => 'nullable|string',
                'inv_description' => 'required|string',
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

            $vendCode = $validatedData['vend_code'];
            $vendAccount = $validatedData['dpp_account'];
            $invStatus = $validatedData['inv_status'];
            $invType = $validatedData['inv_type'];
            $rnID = $validatedData['rn_id'];

            $this->connFirst->beginTransaction();

            $getVend = VendorsGeneral::where('vend_code', $vendCode)->first();

            if (!$getVend) {
                $newVendor = VendorsEPRS::where('id', $vendCode)->first();
                VendorsGeneral::create([
                    'vend_code' => $newVendor->id,
                    'account' => $vendAccount,
                    'vend_short_name' => $newVendor->ShortName,
                    'vend_name' => $newVendor->VendName,
                    'vend_note_1' => $newVendor->Textstre1,
                    'vend_note_2' => $newVendor->Textstre2,
                ]);
            } else {
                $getVend->update([
                    'account' => $vendAccount,
                ]);
            }

            if ($invStatus == 2 && $invType == 2) {
                if ($getRN = ReceiptNotes::find($rnID)) {
                    $getRN->update(['rn_status' => 6]);
                }
            }

            Invoices::create($validatedData);

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

    public function editData(Request $request, $id): JsonResponse
    {
        try {

            $getInv = Invoices::where('id', $id)->first();

            if (!$getInv) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => "Not found."
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'vend_code' => 'required|integer',
                'inv_type' => 'required|integer',
                'po_number' => 'nullable|string',
                'rn_id' => 'nullable|integer',
                'rn_number' => 'nullable|string',
                'ref_number' => 'nullable|string',
                'inv_number' => 'required|string',
                'inv_status' => 'required|integer',
                'inv_receipt_date' => 'required|date',
                'inv_doc_date' => 'required|date',
                'inv_due_date' => 'required|date',
                'ppn_type' => 'required|integer',
                'ppn_number' => 'nullable|string',
                'ppn_amount' => 'required|numeric',
                'ppn_account' => 'required|string',
                'pph_type' => 'required|integer',
                'pph_number' => 'nullable|string',
                'pph_amount' => 'required|numeric',
                'pph_account' => 'nullable|string',
                'dpp_amount' => 'required|numeric',
                'dpp_account' => 'required|string',
                'inv_amount' => 'required|numeric',
                'inv_not_payed' => 'required|numeric',
                'inv_note' => 'nullable|string',
                'inv_description' => 'required|string',
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

            $vendCode = $validatedData['vend_code'];
            $vendAccount = $validatedData['dpp_account'];
            $invStatus = $validatedData['inv_status'];
            $invType = $validatedData['inv_type'];
            $rnID = $validatedData['rn_id'];

            $this->connFirst->beginTransaction();

            $getVend = VendorsGeneral::where('vend_code', $vendCode)->first();

            if (!$getVend) {
                $newVendor = VendorsEPRS::where('id', $vendCode)->first();
                VendorsGeneral::create([
                    'vend_code' => $newVendor->id,
                    'account' => $vendAccount,
                    'vend_short_name' => $newVendor->ShortName,
                    'vend_name' => $newVendor->VendName,
                    'vend_note_1' => $newVendor->Textstre1,
                    'vend_note_2' => $newVendor->Textstre2,
                ]);
            } else {
                $getVend->update([
                    'account' => $vendAccount,
                ]);
            }

            if ($invStatus == 2 && $invType == 2) {
                if ($getRN = ReceiptNotes::find($rnID)) {
                    $getRN->update(['rn_status' => 6]);
                }
            }

            $getInv->update($validatedData);

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
