<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\RoleUsers;
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

    public function searchData(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword');

            $data = $this->usersModel->searchData($keyword);

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

            $data = Users::with('roleUsers.roles')->paginate($perPage);

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

    public function getUserDetail($id): JsonResponse
    {
        try {

            $data = Users::with('roleUsers.roles')->find($id);

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

    public function setStatus($id): JsonResponse
    {
        try {
            $data = Users::find($id);
            $this->connFirst->beginTransaction();

            if ($data) {
                $roles = RoleUsers::where('user_id', $id)->get();
                $newStatus = $data->status == 0 ? 1 : 0;

                $data->update(['status' => $newStatus]);

                foreach ($roles as $role) {
                    $role->update(['status' => $newStatus]);
                }

                $this->connFirst->commit();

                return response()->json([
                    "code" => 200,
                    "status" => true,
                ], 200);
            } else {
                $this->connFirst->rollBack();
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "Not found.",
                ], 404);
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


    public function saveData(Request $request): JsonResponse
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'username' => 'required|string',
                'password' => 'required|string',
                'role_id' => 'required|array',
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
            $roleData = $validatedData['role_id'];
            $dep = $validatedData['dep_code'];

            $this->connFirst->beginTransaction();

            unset($validatedData['role_id']);
            unset($validatedData['dep_code']);

            $newUser = Users::create($validatedData);

            foreach ($roleData as $role) {
                if ($role == 4) {
                    RoleUsers::create([
                        'user_id' => $newUser->id,
                        'role_id' => $role,
                        'status' => 1,
                        'dep_code' => $dep
                    ]);
                } else {
                    RoleUsers::create([
                        'user_id' => $newUser->id,
                        'role_id' => $role,
                        'status' => 1,
                    ]);
                }
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
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
