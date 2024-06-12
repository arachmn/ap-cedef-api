<?php

namespace App\Http\Controllers\AccApp;

use App\Http\Controllers\Controller;
use App\Models\AccApp\VendorAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorAccountsController extends Controller
{
    protected $vendorAccountsModel;

    public function __construct()
    {
        $this->vendorAccountsModel = new VendorAccounts();
    }

    public function searchData(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword');

            $data = $this->vendorAccountsModel->searchData($keyword);

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
}
