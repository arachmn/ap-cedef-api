<?php

namespace App\Http\Controllers\AccApp;

use App\Http\Controllers\Controller;
use App\Models\AccApp\BankAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountsController extends Controller
{

    protected $bankAccountsModel;

    public function __construct()
    {
        $this->bankAccountsModel = new BankAccounts();
    }

    public function searchData(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword');

            $data = $this->bankAccountsModel->searchData($keyword);

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
