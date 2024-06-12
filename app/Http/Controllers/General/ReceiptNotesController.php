<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\ApprovalDataHeaders;
use App\Models\General\ApprovalHeaders;
use App\Models\General\Approvals;
use App\Models\General\DeliveryOrders;
use App\Models\General\ReceiptNotes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;


class ReceiptNotesController extends Controller
{
    protected $connFirst, $receiptNotesModel;

    public function __construct()
    {
        $this->connFirst = DB::connection('connection_first');
        $this->receiptNotesModel = new ReceiptNotes();
    }

    public function searchData(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword');
            $code = $request->input('code');
            $dep = $request->input('dep');

            $data = $this->receiptNotesModel->searchData($code, $dep, $keyword);

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
            $status = $request->input('status', 7);
            $dept = $request->input('dept');
            $dateStart = $request->input('dateStart');
            $dateEnd = $request->input('dateEnd');

            $data = $this->receiptNotesModel->getData($perPage, $status, $dept, $dateStart, $dateEnd);

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

    public function getDataApprove(Request $request, $id): JsonResponse
    {
        try {

            $perPage = $request->input('perPage', 50);

            $data = $this->receiptNotesModel->getDataApprove($perPage, $id);

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

    public function exportDocx($id)
    {
        try {
            $data = ReceiptNotes::with('deliveryOrders.vendors')->find($id);

            if (!$data) {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "Not found.",
                ], 404);
            }

            $templatePath = base_path('public/form/template_rn.docx');
            if (!file_exists($templatePath)) {
                return response()->json([
                    'code' => 500,
                    'status' => false,
                    'message' => 'Template file not found.'
                ], 500);
            }

            $unix = uniqid();
            $fileDOCX = "{$unix}.docx";
            $outputDOCXPath = base_path("public/form/{$fileDOCX}");

            if (file_exists($outputDOCXPath)) {
                return response()->json([
                    'code' => 500,
                    'status' => false,
                    'message' => 'Copy file already exists.'
                ], 500);
            }

            if (!copy($templatePath, $outputDOCXPath)) {
                return response()->json([
                    'code' => 500,
                    'status' => false,
                    'message' => 'Failed to copy the template file.'
                ], 500);
            }

            $templateProcessor = new TemplateProcessor($outputDOCXPath);

            if (!$templateProcessor) {
                return response()->json([
                    'code' => 500,
                    'status' => false,
                    'message' => 'Failed to create Template Processor.'
                ], 500);
            }

            if ($data->rnt_id && $data->deliveryOrders) {
                $formattedDates = [
                    'rnDate' => Carbon::parse($data->rn_date)->format('d-m-Y'),
                    'poDate' => Carbon::parse($data->deliveryOrders->po_date)->format('d-m-Y'),
                    'doDate' => Carbon::parse($data->deliveryOrders->do_date)->format('d-m-Y'),
                    'rnItemDate' => Carbon::parse($data->rn_receipt_date)->format('d-m-Y')
                ];

                $items = json_decode(base64_decode($data->rn_description), true);

                $templateProcessor->setValue('x1', $data->rnt_id == 1 ? 'X' : '');
                $templateProcessor->setValue('x2', $data->rnt_id == 2 ? 'X' : '');
                $templateProcessor->setValue('x3', $data->rnt_id == 3 ? 'X' : '');
                $templateProcessor->setValue('x4', $data->rnt_id == 4 ? 'X' : '');
                $templateProcessor->setValue('rnNum', $data->rn_number);
                $templateProcessor->setValue('rnDate', $formattedDates['rnDate']);
                $templateProcessor->setValue('doNumber', $data->deliveryOrders->do_number);
                $templateProcessor->setValue('doDate', $formattedDates['doDate']);
                $templateProcessor->setValue('poDate', $formattedDates['poDate']);
                $templateProcessor->setValue('poNumber', $data->deliveryOrders->po_number);
                $templateProcessor->setValue('rnItemDate', $formattedDates['rnItemDate']);
                $templateProcessor->setValue('vendName', $data->deliveryOrders->vendors->vend_name);

                $itemNo = '';
                $itemDescriptions = '';
                $itemCounts = '';
                $itemAmounts = '';
                $itemNotes = '';

                foreach ($items as $item) {
                    $itemNo .= $item['no'] . "<w:br/>";
                    $itemDescriptions .= $item['description'] . "<w:br/>";
                    $itemCounts .= $item['qty'] . "<w:br/>";
                    $itemAmounts .= $item['amount'] . "<w:br/>";
                    $itemNotes .= $item['note'] . "<w:br/>";
                }

                $templateProcessor->setValue('rnNo', $itemNo);
                $templateProcessor->setValue('rnItemDesc', $itemDescriptions);
                $templateProcessor->setValue('rnCountItem', $itemCounts);
                $templateProcessor->setValue('rnAmountItem', $itemAmounts);
                $templateProcessor->setValue('rnNoteItem', $itemNotes);


                $templateProcessor->saveAs($outputDOCXPath);

                $fileContents = file_get_contents($outputDOCXPath);

                $base64File = base64_encode($fileContents);

                unlink($outputDOCXPath);

                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'data' => $base64File
                ], 200);
            } else {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'Error'
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
            $data = ReceiptNotes::with('receiptNoteTypes', 'deliveryOrders.vendors', 'deliveryOrders.purchaseOrders', 'users', 'approvalDataHeaders.approvals.users', 'approvalDataHeaders.approvalHeaders')->where('id', $id)->first();
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
                'do_id' => 'required|integer',
                'approval' => 'required|string',
                'rn_receipt_date' => 'required|date',
                'rnt_id' => 'required|integer',
                'rn_note' => 'nullable|string',
                'rn_description' => 'required|string',
                'rn_status' => 'required|integer',
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

            $apvhCode = $validatedData['approval'];
            $doId = $validatedData['do_id'];
            $depCode = $validatedData['dep_code'];
            $rnStatus = $validatedData['rn_status'];

            $this->connFirst->beginTransaction();

            $lastRN = ReceiptNotes::withTrashed()->where('dep_code', $depCode)
                ->whereMonth('rn_date', Carbon::now()->month)
                ->whereYear('rn_date', Carbon::now()->year)
                ->selectRaw('CAST(SUBSTRING(rn_number, 1, 4) AS UNSIGNED) as rn_code')
                ->orderByDesc('rn_code')
                ->first();

            $lastRnNumber = $lastRN ? $lastRN->rn_code : 0;

            $lastApvId = ApprovalDataHeaders::withTrashed()->max('id') ?? 0;

            $lisUserApv = ApprovalHeaders::with('approvalUsers.users')
                ->whereHas('approvalUsers.users')
                ->where('apvh_code', $apvhCode)
                ->first();

            if ($lisUserApv) {
                $apvdhCode = "APVDH-" . str_pad($lastApvId + 1, 8, '0', STR_PAD_LEFT);

                ApprovalDataHeaders::create([
                    'apvdh_code' => $apvdhCode,
                    'apvh_code' =>  $apvhCode,
                ]);

                foreach ($lisUserApv->approvalUsers as $approvalUser) {
                    Approvals::create([
                        'apvdh_code' => $apvdhCode,
                        'apv_level' =>  $approvalUser->apvu_level,
                        'user_id' =>  $approvalUser->user_id,
                        'apv_status' =>  1
                    ]);
                }

                $validatedData['rn_number'] = str_pad($lastRnNumber + 1, 4, '0', STR_PAD_LEFT) . "/NTB/" . $depCode . "/" . date('m/y');
                $validatedData['apvdh_code'] = $apvdhCode;
                $validatedData['rn_date'] = date('Y-m-d');

                unset($validatedData['approval']);

                ReceiptNotes::create($validatedData);

                if ($rnStatus == 2) {
                    DeliveryOrders::where('id', $doId)
                        ->update([
                            'do_status' => 4
                        ]);
                }

                $this->connFirst->commit();

                return response()->json([
                    "code" => 200,
                    "status" => true
                ], 200);
            } else {
                $this->connFirst->rollBack();
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => 'Data approval tidak ditemukan'
                ], 422);
            }
        } catch (\Throwable $th) {
            $this->connFirst->rollBack();
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $th->getMessage()
            ], 500);
        }
    }

    public function editData(Request $request, $id): JsonResponse
    {
        try {
            $getRN = ReceiptNotes::where('id', $id)->with('approvalDataHeaders')->first();

            if (!$getRN) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => "Not found."
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'do_number' => 'required|string',
                'do_id' => 'required|integer',
                'approval' => 'required|string',
                'rn_receipt_date' => 'required|date',
                'rnt_id' => 'required|integer',
                'rn_note' => 'nullable|string',
                'rn_description' => 'required|string',
                'rn_status' => 'required|integer',
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

            $apvhCode = $validatedData['approval'];
            $rnStatus = $validatedData['rn_status'];
            $doId = $validatedData['do_id'];

            $this->connFirst->beginTransaction();

            if ($apvhCode != $getRN->approvalDataHeaders->apvh_code) {
                $lastApvId = ApprovalDataHeaders::withTrashed()->max('id') ?? 0;

                $lisUserApv = ApprovalHeaders::with('approvalUsers.users')
                    ->whereHas('approvalUsers.users')
                    ->where('apvh_code', $apvhCode)
                    ->first();

                if ($lisUserApv) {
                    $apvdhCode = "APVDH-" . str_pad($lastApvId + 1, 8, '0', STR_PAD_LEFT);

                    ApprovalDataHeaders::create([
                        'apvdh_code' => $apvdhCode,
                        'apvh_code' =>  $apvhCode,
                    ]);

                    foreach ($lisUserApv->approvalUsers as $approvalUser) {
                        Approvals::create([
                            'apvdh_code' => $apvdhCode,
                            'apv_level' =>  $approvalUser->apvu_level,
                            'user_id' =>  $approvalUser->user_id,
                            'apv_status' =>  1
                        ]);
                    }
                    $validatedData['apvdh_code'] = $apvdhCode;
                } else {
                    return response()->json([
                        'code' => 422,
                        'status' => false,
                        'message' => 'Data approval tidak ditemukan'
                    ], 422);
                }
            }

            if ($rnStatus == 2) {
                DeliveryOrders::where('id', $doId)
                    ->update([
                        'do_status' => 4
                    ]);
            }

            $validatedData['rn_date'] = date('Y-m-d');

            unset($validatedData['approval']);

            $getRN->update($validatedData);

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
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $th->getMessage()
            ], 500);
        }
    }

    public function setApproval(Request $request, $id): JsonResponse
    {
        try {
            $getRN = ReceiptNotes::where('id', $id)->with('approvalDataHeaders.approvals')->first();

            $getApvMax = Approvals::where('apvdh_code', $getRN->approvalDataHeaders->apvdh_code)->max('apv_level');



            if (!$getRN) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => "Not found."
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'apv_status' => 'required|integer',
                'apv_note' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $validatedData = $validator->validated();

            $apvStatus = $validatedData['apv_status'];
            $userId = $validatedData['user_id'];

            $getApvUser = Approvals::where('apvdh_code', $getRN->approvalDataHeaders->apvdh_code)->where('user_id', $userId)->first();

            if ($apvStatus == 3) {
                $getRN->update([
                    'rn_status' => 5
                ]);
            }

            if ($getApvMax == $getApvUser->apv_level && $apvStatus == 2) {
                $getRN->update([
                    'rn_status' => 4
                ]);
            }

            $validatedData['apv_date'] = date('Y-m-d');


            $getApvUser->update($validatedData);

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
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $th->getMessage()
            ], 500);
        }
    }
}
