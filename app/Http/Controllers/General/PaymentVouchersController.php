<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\ApprovalDataHeaders;
use App\Models\General\ApprovalHeaders;
use App\Models\General\Approvals;
use App\Models\General\Invoices;
use App\Models\General\PaymentVoucherInvoices;
use App\Models\General\PaymentVouchers;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;
use Riskihajar\Terbilang\Facades\Terbilang;
use Illuminate\Support\Facades\Config;



class PaymentVouchersController extends Controller
{

    protected $connFirst, $paymentVouchersModel;

    public function __construct()
    {
        $this->connFirst = DB::connection('connection_first');
        $this->paymentVouchersModel = new PaymentVouchers();
    }

    public function getAging(): JsonResponse
    {
        try {
            $data = $this->paymentVouchersModel->getAgingAll();

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

    public function getAgingVendor($id): JsonResponse
    {
        try {

            $data = $this->paymentVouchersModel->getAgingDetail($id);

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
            $data = PaymentVouchers::with('vendors', 'paymentVoucherInvoices.invoices.purchaseOrders', 'paymentVoucherInvoices.invoices.receiptNotes.deliveryOrders.purchaseOrders')->find($id);

            if (!$data) {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "Not found.",
                ], 404);
            }

            $templatePath = base_path('public/form/template_pv.docx');
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

            $ppNumbers = [];

            foreach ($data->paymentVoucherInvoices as $invoice) {
                $invoiceData = $invoice->invoices;
                if ($invoiceData && $invoiceData->inv_type == 2) {
                    $receiptNotes = $invoiceData->receiptNotes ?? null;
                    $deliveryOrders = $receiptNotes->deliveryOrders ?? null;
                    $purchaseOrders = $deliveryOrders->purchaseOrders ?? null;
                    if ($purchaseOrders) {
                        $ppNumbers[] = $purchaseOrders->first()->pp_no;
                    }
                }
            }

            foreach ($data->paymentVoucherInvoices as $invoice) {
                $invoiceData = $invoice->invoices;
                if ($invoiceData && $invoiceData->inv_type == 1) {
                    $purchaseOrders = $invoiceData->purchaseOrders ?? null;
                    if ($purchaseOrders) {
                        $ppNumbers[] = $purchaseOrders->first()->pp_no;
                    }
                }
            }

            $formattedDates = [
                'pv_due_date' => Carbon::parse($data->pv_due_date)->format('d-m-Y')
            ];

            for ($i = 0; $i < 13; $i++) {
                $dppAccount = $data->dpp_account[$i] ?? "";
                $ppnAccount = $data->ppn_account[$i] ?? "";
                $pphAccount = $data->pph_account[$i] ?? "";

                $templateProcessor->setValue('a' . ($i + 1), $dppAccount == 0 ? "O" : $dppAccount);
                $templateProcessor->setValue('b' . ($i + 1), $ppnAccount == 0 ? "O" : $ppnAccount);
                $templateProcessor->setValue('c' . ($i + 1), $pphAccount == 0 ? "O" : $pphAccount);
            }

            $dppNote = $data->dpp_account != null && $data->dpp_account != "" ? 'DPP' : "";
            $ppnNote = $data->ppn_account != null && $data->ppn_account != "" ? 'PPN' : "";
            $pphNote = $data->pph_account != null && $data->pph_account != "" ? 'PPH' : "";

            $templateProcessor->setValue('dpp_amount', number_format(intval($data->dpp_amount), 0, ',', '.'));
            $templateProcessor->setValue('ppn_amount', number_format(intval($data->ppn_amount), 0, ',', '.'));
            $templateProcessor->setValue('pph_amount', number_format(intval($data->pph_amount), 0, ',', '.'));
            $templateProcessor->setValue('total_amount', number_format(intval($data->pv_amount), 0, ',', '.'));
            $templateProcessor->setValue('dpp_note', $dppNote);
            $templateProcessor->setValue('ppn_note', $ppnNote);
            $templateProcessor->setValue('pph_note', $pphNote);
            $templateProcessor->setValue('pv_number', $data->pv_number);
            $templateProcessor->setValue('pv_due_date', $formattedDates['pv_due_date']);

            $invDesc = '';
            $invDescDPPAmount = '';
            $ppnNumbers = '';

            foreach ($data->paymentVoucherInvoices as $item) {
                $invoiceData = $item['invoices'];
                if ($invoiceData && $invoiceData['inv_type'] == 2) {
                    $receiptNotes = $invoiceData['receiptNotes'] ?? null;
                    $deliveryOrders = $receiptNotes['deliveryOrders'] ?? null;
                    $purchaseOrders = $deliveryOrders['purchaseOrders'] ?? null;
                    $ppNo = $purchaseOrders ? implode('/', array_slice(explode('/', $purchaseOrders['pp_no']), -3)) : '';
                } else {
                    $purchaseOrders = $invoiceData['purchaseOrders'] ?? null;
                    $ppNo = $purchaseOrders ? implode('/', array_slice(explode('/', $purchaseOrders['pp_no']), -3)) : '';
                }

                $dppAmt = $item['dpp_amount'] ?? 0;

                $rnNumber = substr($invoiceData['receiptNotes']['rn_number'] ?? '', 0, 4);
                $ppnNumber = substr($invoiceData['ppn_number'] ?? '', -3);
                $ppnfullNum = $invoiceData['ppn_number'] ?? '';

                $invDesc .= $item['inv_number'] . " NTB: " . $rnNumber . " No. FP: " . $ppnNumber . " PP: " . $ppNo . "<w:br/>";
                $ppnNumbers .= $ppnfullNum . ", ";
                $invDescDPPAmount .= number_format(intval($dppAmt), 0, ',', '.') . "<w:br/>";
            }

            Config::set('terbilang.locale', 'id');

            $templateProcessor->setValue('inv_desc_amount', $invDescDPPAmount);
            $templateProcessor->setValue('inv_desc', $invDesc);
            $templateProcessor->setValue('ppn_number', $ppnNumbers);
            $templateProcessor->setValue('vend_name', $data->vendors->vend_name ?? '');
            $templateProcessor->setValue('amount_detail',  str_replace("senilai ", "", Terbilang::make(intval($data->pv_amount), ' rupiah', 'senilai ')));

            $templateProcessor->saveAs($outputDOCXPath);

            $fileContents = file_get_contents($outputDOCXPath);

            $base64File = base64_encode($fileContents);

            unlink($outputDOCXPath);

            return response()->json([
                'code' => 200,
                'status' => true,
                'data' => $base64File
            ], 200);
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
            $dateStart = $request->input('dateStart');
            $dateEnd = $request->input('dateEnd');

            $data = $this->paymentVouchersModel->getData($perPage, $status, $dateStart, $dateEnd);

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
            $data = PaymentVouchers::with('taxAccounts', 'pphAccounts', 'vendorAccounts', 'vendors', 'users', 'approvalDataHeaders.approvals.users', 'approvalDataHeaders.approvalHeaders', 'paymentVoucherInvoices.invoices.taxAccounts', 'paymentVoucherInvoices.invoices.pphAccounts')->where('id', $id)->first();
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
                'dpp_account' => 'nullable|numeric',
                'ppn_account' => 'required|numeric',
                'pph_account' => 'nullable|numeric',
                'pv_status' => 'required|integer',
                'pv_due_date' => 'required|date',
                'ppn_amount' => 'required|numeric',
                'pph_amount' => 'required|numeric',
                'dpp_amount' => 'required|numeric',
                'pv_amount' => 'required|numeric',
                'pv_note' => 'nullable|string',
                'inv_data' => 'required|array',
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

            $apvhCode = ApprovalHeaders::where('apvh_target', 2)->where('apvh_status', 1)->first();
            $invData = $validatedData['inv_data'];

            if ($apvhCode) {
                $this->connFirst->beginTransaction();

                $lastPV = PaymentVouchers::withTrashed()
                    ->whereMonth('pv_doc_date', Carbon::now()->month)
                    ->whereYear('pv_doc_date', Carbon::now()->year)
                    ->selectRaw('CAST(SUBSTRING(pv_number, LENGTH(pv_number) - 3, 4) AS UNSIGNED) AS pv_code')
                    ->orderByDesc('pv_code')
                    ->first();


                $lastPVNumber = $lastPV ? $lastPV->pv_code : 0;

                $lastApvId = ApprovalDataHeaders::withTrashed()->max('id') ?? 0;

                $lisUserApv = ApprovalHeaders::with('approvalUsers.users')
                    ->whereHas('approvalUsers.users')
                    ->where('apvh_code', $apvhCode->apvh_code)
                    ->first();

                if ($lisUserApv) {
                    $apvdhCode = "APVDH-" . str_pad($lastApvId + 1, 8, '0', STR_PAD_LEFT);

                    ApprovalDataHeaders::create([
                        'apvdh_code' => $apvdhCode,
                        'apvh_code' =>  $apvhCode->apvh_code,
                    ]);

                    foreach ($lisUserApv->approvalUsers as $approvalUser) {
                        if ($approvalUser->apvu_level == 1) {
                            Approvals::create([
                                'apvdh_code' => $apvdhCode,
                                'apv_level' =>  $approvalUser->apvu_level,
                                'apv_open' =>  1,
                                'user_id' =>  $approvalUser->user_id,
                                'apv_status' =>  1
                            ]);
                        } else {
                            Approvals::create([
                                'apvdh_code' => $apvdhCode,
                                'apv_level' =>  $approvalUser->apvu_level,
                                'user_id' =>  $approvalUser->user_id,
                                'apv_status' =>  1
                            ]);
                        }
                    }

                    $validatedData['pv_number'] = "CDF-LL-" . date('Y') . "-" . str_pad($lastPVNumber + 1, 4, '0', STR_PAD_LEFT);
                    $validatedData['apvdh_code'] = $apvdhCode;
                    $validatedData['pv_doc_date'] = date('Y-m-d');

                    unset($validatedData['inv_data']);

                    $paymentVoucher = PaymentVouchers::create($validatedData);

                    foreach ($invData as $inv) {
                        $invoice = Invoices::where('inv_number', $inv)->first();
                        PaymentVoucherInvoices::create([
                            'pv_id' => $paymentVoucher->id,
                            'inv_id' => $invoice['id'],
                            'inv_number' => $invoice['inv_number'],
                            'ppn_amount' => $invoice['ppn_amount'],
                            'pph_amount' => $invoice['pph_amount'],
                            'dpp_amount' => $invoice['dpp_amount'],
                            'inv_amount' => $invoice['inv_amount'],
                        ]);
                        $invoice->update(['inv_status' => 4]);
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
            } else {
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
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getDataApprove(Request $request, $id): JsonResponse
    {
        try {

            $perPage = $request->input('perPage', 50);
            $status = $request->input('status', 7);

            $data = $this->paymentVouchersModel->getDataApprove($perPage, $status, $id);

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

    public function setApproval(Request $request, $id): JsonResponse
    {
        try {
            $getPV = PaymentVouchers::where('id', $id)->with('approvalDataHeaders.approvals', 'paymentVoucherInvoices')->first();

            $getApvMax = Approvals::where('apvdh_code', $getPV->approvalDataHeaders->apvdh_code)->max('apv_level');

            if (!$getPV) {
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

            $this->connFirst->beginTransaction();

            $getApvUser = Approvals::where('apvdh_code', $getPV->approvalDataHeaders->apvdh_code)->where('user_id', $userId)->first();
            $getNextApv = Approvals::where('apvdh_code', $getPV->approvalDataHeaders->apvdh_code)->where('apv_level', $getApvUser->apv_level + 1)->first();

            if ($apvStatus == 3) {
                foreach ($getPV->paymentVoucherInvoices as $inv) {
                    $getInv = Invoices::find($inv->inv_id);
                    if ($getInv) {
                        $getInv->update(['inv_status' => 2]);
                    }
                }
                $getPV->update([
                    'pv_status' => 5
                ]);
            }

            if ($getApvMax == $getApvUser->apv_level && $apvStatus == 2) {
                $getPV->update([
                    'pv_status' => 4
                ]);
            }

            if ($getNextApv) {
                $getNextApv->update(['apv_open' => 1]);
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
