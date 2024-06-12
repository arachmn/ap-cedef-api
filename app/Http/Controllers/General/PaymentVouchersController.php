<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\ApprovalDataHeaders;
use App\Models\General\ApprovalHeaders;
use App\Models\General\Approvals;
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

    public function exportDocx($id)
    {
        try {
            $data = PaymentVouchers::with('vendors', 'paymentVoucherInvoices.invoices.purchaseOrders', 'paymentVoucherInvoices.invoices.receiptNotes.deliveryOrders.purchaseOrders', 'bankAccounts')->find($id);

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
                if (
                    isset($invoice->invoices) &&
                    $invoice->invoices->inv_type == 2 &&
                    isset($invoice->invoices->receiptNotes) &&
                    isset($invoice->invoices->receiptNotes->deliveryOrders) &&
                    isset($invoice->invoices->receiptNotes->deliveryOrders->purchaseOrders)
                ) {
                    $ppNumbers[] = $invoice->invoices->receiptNotes->deliveryOrders->purchaseOrders->first()->pp_no;
                }
            }

            foreach ($data->paymentVoucherInvoices as $invoice) {
                if (
                    isset($invoice->invoices) &&
                    $invoice->invoices->inv_type == 1 &&
                    isset($invoice->invoices->purchaseOrders)
                ) {
                    $ppNumbers[] = $invoice->invoices->purchaseOrders->first()->pp_no;
                }
            }
            $formattedDates = [
                'pv_due_date' => Carbon::parse($data->pv_due_date)->format('d-m-Y')
            ];

            $invItems = $data->paymentVoucherInvoices;

            for ($i = 0; $i < 13; $i++) {
                if ($data->dpp_account[$i] == 0) {
                    $templateProcessor->setValue('a' . $i + 1, "O");
                } else {
                    $templateProcessor->setValue('a' . $i + 1, $data->dpp_account[$i]);
                }
                if ($data->ppn_account[$i] == 0) {
                    $templateProcessor->setValue('b' . $i + 1, "O");
                } else {
                    $templateProcessor->setValue('b' . $i + 1, $data->ppn_account[$i]);
                }
                if ($data->pph_account[$i] == 0) {
                    $templateProcessor->setValue('c' . $i + 1, "O");
                } else {
                    $templateProcessor->setValue('c' . $i + 1, $data->pph_account[$i]);
                }
            }

            $templateProcessor->setValue('bank', $data->bankAccounts->bankname);
            // $templateProcessor->setValue('ppn_account',  preg_replace('/./', '$0    ', $data->ppn_account));
            // $templateProcessor->setValue('pph_account',  preg_replace('/./', '$0    ', $data->pph_account));
            $templateProcessor->setValue('dpp_amount', number_format(intval($data->dpp_amount), 0, ',', '.'));
            $templateProcessor->setValue('ppn_amount', number_format(intval($data->ppn_amount), 0, ',', '.'));
            $templateProcessor->setValue('pph_amount', number_format(intval($data->pph_amount), 0, ',', '.'));
            $templateProcessor->setValue('total_amount', number_format(intval($data->pv_amount), 0, ',', '.'));
            $templateProcessor->setValue('dpp_note', 'DPP');
            $templateProcessor->setValue('ppn_note', 'PPN');
            $templateProcessor->setValue('pph_note', 'PPH');
            $templateProcessor->setValue('pv_number', $data->pv_number);
            $templateProcessor->setValue('pv_due_date', $formattedDates['pv_due_date']);

            $invDesc = '';
            $invDescDPPAmount = '';
            $ppnNumbers = '';

            foreach ($invItems as $item) {
                if (isset($item['invoices']['inv_type']) && $item['invoices']['inv_type'] == 2) {
                    $ppNo = isset($item['invoices']['receiptNotes']['deliveryOrders']['purchaseOrders']['pp_no']) ? implode('/', array_slice(explode('/', $item['invoices']['receiptNotes']['deliveryOrders']['purchaseOrders']['pp_no']), -3)) : '';
                } else {
                    $ppNo = isset($item['invoices']['purchaseOrders']['pp_no']) ? implode('/', array_slice(explode('/', $item['invoices']['purchaseOrders']['pp_no']), -3)) : '';
                }

                $dppAmt = $item['dpp_amount'];

                $rnNumber = isset($item['invoices']['receiptNotes']['rn_number']) ? substr($item['invoices']['receiptNotes']['rn_number'], 0, 4) : '';
                $ppnNumber = isset($item['invoices']['ppn_number']) ? substr($item['invoices']['ppn_number'], -3) : '';
                $ppnfullNum = isset($item['invoices']['ppn_number']) ? $item['invoices']['ppn_number'] : '';

                $invDesc .= $item['inv_number'] . " NTB: " . $rnNumber . " No. FP: " . $ppnNumber . " PP: " . $ppNo . "<w:br/>";
                $ppnNumbers .= $ppnfullNum . ", ";
                $invDescDPPAmount .= number_format(intval($dppAmt), 0, ',', '.') . "<w:br/>";
            }

            Config::set('terbilang.locale', 'id');

            $templateProcessor->setValue('inv_desc_amount', $invDescDPPAmount);
            $templateProcessor->setValue('inv_desc', $invDesc);
            $templateProcessor->setValue('ppn_number', $ppnNumbers);
            $templateProcessor->setValue('vend_name', $data->vendors->vend_name);
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
            $data = PaymentVouchers::with('taxAccounts', 'bankAccounts', 'pphAccounts', 'vendorAccounts', 'vendors', 'users', 'approvalDataHeaders.approvals.users', 'approvalDataHeaders.approvalHeaders', 'paymentVoucherInvoices.invoices.taxAccounts', 'paymentVoucherInvoices.invoices.pphAccounts')->where('id', $id)->first();
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
                'dpp_account' => 'required|numeric',
                'ppn_account' => 'required|numeric',
                'pph_account' => 'required|numeric',
                'approval' => 'required|string',
                'pv_status' => 'required|integer',
                'pv_due_date' => 'required|date',
                'bank_id' => 'required|integer',
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

            $apvhCode = $validatedData['approval'];
            $invData = $validatedData['inv_data'];

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

                $validatedData['pv_number'] = "CDF-LL-" . date('Y') . "-" . str_pad($lastPVNumber + 1, 4, '0', STR_PAD_LEFT);
                $validatedData['apvdh_code'] = $apvdhCode;
                $validatedData['pv_doc_date'] = date('Y-m-d');

                unset($validatedData['approval']);
                unset($validatedData['inv_data']);

                $paymentVoucher = PaymentVouchers::create($validatedData);

                foreach ($invData as $inv) {
                    PaymentVoucherInvoices::create([
                        'pv_id' => $paymentVoucher->id,
                        'inv_id' => $inv['inv_id'],
                        'inv_number' => $inv['inv_number'],
                        'ppn_amount' => $inv['ppn'],
                        'pph_amount' => $inv['pph'],
                        'dpp_amount' => $inv['dpp'],
                        'inv_amount' => $inv['inv'],
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
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function editData(Request $request, $id): JsonResponse
    {
        try {
            $getPV = PaymentVouchers::where('id', $id)->with('approvalDataHeaders')->first();

            if (!$getPV) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => "Not found."
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'vend_code' => 'required|integer',
                'dpp_account' => 'required|numeric',
                'ppn_account' => 'required|numeric',
                'pph_account' => 'required|numeric',
                'approval' => 'required|string',
                'pv_status' => 'required|integer',
                'pv_due_date' => 'required|date',
                'bank_id' => 'required|integer',
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

            $invData = $validatedData['inv_data'];
            $apvhCode = $validatedData['approval'];

            $this->connFirst->beginTransaction();

            if ($apvhCode != $getPV->approvalDataHeaders->apvh_code) {
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

            $validatedData['pv_doc_date'] = date('Y-m-d');

            unset($validatedData['approval']);
            unset($validatedData['inv_data']);

            $getPV->update($validatedData);

            PaymentVoucherInvoices::where('pv_id', $id)->delete();

            foreach ($invData as $inv) {
                PaymentVoucherInvoices::create([
                    'pv_id' => $id,
                    'inv_id' => $inv['inv_id'],
                    'inv_number' => $inv['inv_number'],
                    'ppn_amount' => $inv['ppn'],
                    'pph_amount' => $inv['pph'],
                    'dpp_amount' => $inv['dpp'],
                    'inv_amount' => $inv['inv'],
                ]);
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
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $th->getMessage()
            ], 500);
        }
    }
}
