<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function getData(): JsonResponse
    {
        try {

            $data = Roles::all();

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
