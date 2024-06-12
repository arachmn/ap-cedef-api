<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\Users;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{

    protected $connFirst, $usersModel;

    public function __construct()
    {
        $this->connFirst = DB::connection('connection_first');
        $this->usersModel = new Users();
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('perPage', 50);

            $data = Users::with('roles', 'departements')->paginate($perPage);

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
                'name' => 'required|string',
                'username' => 'required|string',
                'password' => 'required|string',
                'role_id' => 'required|integer',
                'dep_code' => 'required|string',
                'status' => 'required|integer',
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }


            $validatedData = $validator->validated();

            $validatedData['password'] = Hash::make($validatedData['password']);

            $this->connFirst->beginTransaction();

            Users::create($validatedData);

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
}
