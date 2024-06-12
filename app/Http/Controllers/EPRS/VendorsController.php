<?php

namespace App\Http\Controllers\EPRS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EPRS\Vendors;
use Illuminate\Http\JsonResponse;

class VendorsController extends Controller
{

    protected $vendorsModel;

    public function __construct()
    {
        $this->vendorsModel = new Vendors();
    }

    public function searchData(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword');

            $data = $this->vendorsModel->searchData($keyword);

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
