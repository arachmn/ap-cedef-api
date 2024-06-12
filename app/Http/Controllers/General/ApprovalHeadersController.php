<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\ApprovalHeaders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalHeadersController extends Controller
{
    public function getData(Request $request): JsonResponse
    {
        try {
            $data = ApprovalHeaders::with('approvalUsers.users')->whereHas('approvalUsers.users')->get();

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
