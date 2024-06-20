<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\DeliveryOrders;
use App\Models\EPRS\Vendors as VendorsEPRS;
use App\Models\General\ReceiptNotes;
use App\Models\General\Vendors as VendorsGeneral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DeliveryOrdersController extends Controller
{
    protected $deliveryOrdersModel, $vendorsEPRSModel, $vendorsGeneralModel, $connFirst;

    public function __construct()
    {
        $this->connFirst = DB::connection('connection_first');
        $this->deliveryOrdersModel = new DeliveryOrders();
        $this->vendorsEPRSModel = new VendorsEPRS();
        $this->vendorsGeneralModel = new VendorsGeneral();
    }

    public function searchData(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword');
            $code = $request->input('code');
            $dep = $request->input('dep');

            $data = $this->deliveryOrdersModel->searchData($code, $dep, $keyword);

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
            $status = $request->input('status', 5);
            $dept = $request->input('dept');
            $dateStart = $request->input('dateStart');
            $dateEnd = $request->input('dateEnd');

            $data = $this->deliveryOrdersModel->getData($perPage, $status, $dept, $dateStart, $dateEnd);

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
            $data = DeliveryOrders::with('vendors', 'users')->where('id', $id)->first();
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
                'dep_code' => 'required|string',
                'do_number' => 'required|string',
                'do_date' => 'required|date',
                'po_number' => 'required|string',
                'vend_code' => 'required|integer',
                'do_note' => 'nullable|string',
                'do_description' => 'required|string',
                'do_amount' => 'required|numeric',
                'do_status' => 'required|integer',
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

            $this->connFirst->beginTransaction();

            $getVend = VendorsGeneral::where('vend_code', $vendCode)->first();

            if (!$getVend) {
                $newVendor = VendorsEPRS::where('id', $vendCode)->first();
                VendorsGeneral::create([
                    'vend_code' => $newVendor->id,
                    'vend_short_name' => $newVendor->ShortName,
                    'vend_name' => $newVendor->VendName,
                    'vend_note_1' => $newVendor->Textstre1,
                    'vend_note_2' => $newVendor->Textstre2,
                ]);
            }

            DeliveryOrders::create($validatedData);

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

            $getDO = DeliveryOrders::where('id', $id)->first();

            if (!$getDO) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => "Not found."
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'do_number' => 'required|string',
                'do_date' => 'required|date',
                'po_number' => 'required|string',
                'vend_code' => 'required|integer',
                'do_note' => 'nullable|string',
                'do_description' => 'required|string',
                'do_amount' => 'required|numeric',
                'do_status' => 'required|integer',
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

            $this->connFirst->beginTransaction();

            $getVend = VendorsGeneral::where('vend_code', $vendCode)->first();

            if (!$getVend) {
                $newVendor = VendorsEPRS::where('id', $vendCode)->first();
                VendorsGeneral::create([
                    'vend_code' => $newVendor->id,
                    'vend_short_name' => $newVendor->ShortName,
                    'vend_name' => $newVendor->VendName,
                    'vend_note_1' => $newVendor->Textstre1,
                    'vend_note_2' => $newVendor->Textstre2,
                ]);
            }

            $getDO->update($validatedData);

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

    public function cancelData($id): JsonResponse
    {
        try {
            $getDO = DeliveryOrders::find($id);

            if (!$getDO) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => "Not found."
                ], 404);
            }
            $getDO->update(['do_status' => 3]);
            return response()->json([
                'code' => 200,
                'status' => true
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
