<?php

namespace App\Http\Controllers\AccApp;

use App\Http\Controllers\Controller;
use App\Models\AccApp\TaxAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxAccountsController extends Controller
{
    public function getData(Request $request): JsonResponse
    {
        try {

            $data = TaxAccounts::all();

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
