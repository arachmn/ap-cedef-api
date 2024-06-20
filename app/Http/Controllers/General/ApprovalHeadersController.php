<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\ApprovalHeaders;
use App\Models\General\ApprovalUsers;
use App\Models\General\RoleUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ApprovalHeadersController extends Controller
{

    protected $approvalHeadersModel, $connFirst;

    public function __construct()
    {
        $this->connFirst = DB::connection('connection_first');
        $this->approvalHeadersModel = new ApprovalHeaders();
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('perPage');
            $status = $request->input('status');
            $data = $this->approvalHeadersModel->getData($perPage, $status);

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
            $data = $this->approvalHeadersModel->getDetail($id);

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

    public function status(Request $request, $id)
    {
        try {
            $status = $request->input('status');

            if ($status == 0 || $status == 1) {
                $apvId = ApprovalHeaders::with('approvalUsers')->find($id);
                if (!$apvId) {
                    return response()->json([
                        "code" => 404,
                        "status" => false,
                        "message" => "Not found.",
                    ], 404);
                } else {
                    $this->connFirst->beginTransaction();
                    $apvId->update([
                        'apvh_status' => $status,
                    ]);

                    $roleDetail = null;
                    if ($apvId->apvh_target == 1) {
                        $roleDetail = 2;
                    } elseif ($apvId->apvh_target == 2) {
                        $roleDetail = 3;
                    }

                    if ($roleDetail !== null) {
                        foreach ($apvId->approvalUsers as $user) {
                            $setRole = RoleUsers::where('user_id', $user->user_id)->where('role_id', $roleDetail)->first();
                            if ($setRole) {
                                $setRole->update([
                                    'status' => $status
                                ]);
                            }
                        }
                    }
                    $this->connFirst->commit();
                    return response()->json([
                        "code" => 200,
                        "status" => true,
                        "message" => "Successfully updated."
                    ], 200);
                }
            } else {
                return response()->json([
                    "code" => 400,
                    "status" => false,
                    "message" => "Invalid params.",
                ], 400);
            }
        } catch (\Throwable $th) {
            $this->connFirst->rollBack();
            return response()->json([
                "code" => 500,
                "status" => false,
                "message" => "An error occurred."
            ], 500);
        }
    }

    public function saveData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dep_code' => 'nullable|string',
                'apvh_description' => 'required|string',
                'apvh_target' => 'required|integer',
                'apvh_status' => 'required|integer',
                'apvh_users' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $validatedData = $validator->validated();

            $users = $validatedData['apvh_users'];
            $dep = $validatedData['dep_code'];
            $target = $validatedData['apvh_target'];

            $getContex = ApprovalHeaders::where('apvh_target', $target)
                ->where('apvh_status', 1);

            if ($target == 1) {
                $getContex->where('dep_code', $dep);
            }

            $getContex = $getContex->first();

            if ($getContex) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'Approval is available'
                ], 400);
            } else {
                $this->connFirst->beginTransaction();

                $lastApvId = ApprovalHeaders::withTrashed()->max('id') ?? 0;

                $apvhCode = "APVH-" . str_pad($lastApvId + 1, 8, '0', STR_PAD_LEFT);

                $validatedData['apvh_code'] = $apvhCode;

                $listUsers = json_decode(base64_decode($users), true);

                foreach ($listUsers as $user) {
                    ApprovalUsers::create([
                        'apvh_code' => $apvhCode,
                        'apvu_level' =>  $user['level'],
                        'user_id' =>  $user['user']['id'],
                        'apvu_description' =>  $user['description'],
                    ]);
                }

                foreach ($listUsers as $user) {
                    $roleId = null;
                    $getRole = RoleUsers::where('user_id', $user['user']['id']);

                    if ($target == 1) {
                        $roleId = 2;
                        $getRole->where('role_id', 2)->where('dep_code', $dep);
                    } elseif ($target == 2) {
                        $roleId = 3;
                        $getRole->where('role_id', 3);
                    }

                    $getRole = $getRole->first();

                    if (!$getRole) {
                        RoleUsers::create([
                            'user_id' => $user['user']['id'],
                            'role_id' => $roleId,
                            'dep_code' => $dep,
                        ]);
                    } else {
                        $getRole->update([
                            'status' => 1
                        ]);
                    }
                }

                unset($validatedData['apvh_users']);

                ApprovalHeaders::create($validatedData);

                $this->connFirst->commit();

                return response()->json([
                    "code" => 200,
                    "status" => true
                ], 200);
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
}
