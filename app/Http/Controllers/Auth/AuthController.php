<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\General\Users;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    "code" => 401,
                    "status" => false,
                    "message" => "Unauthorized: Invalid credentials"
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                "code" => 500,
                "status" => false,
                "message" => "Could not create token"
            ], 500);
        }

        $user = Users::where('username', $request->username)->first();

        return response()->json([
            "code" => 200,
            "status" => true,
            "data" => [
                "user" => $user,
                "token" => $token
            ]
        ], 200);
    }

    public function getAuthenticatedUser()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "User not found"
                ], 404);
            }
        } catch (JWTException $e) {
            return response()->json([
                "code" => 401,
                "status" => false,
                "message" => "Unauthorized: Invalid token"
            ], 401);
        }

        return response()->json([
            "code" => 200,
            "status" => true,
            "data" => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                "code" => 200,
                "status" => true,
                "message" => "Successfully logged out"
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                "code" => 500,
                "status" => false,
                "message" => "Could not log out the user"
            ], 500);
        }
    }
}
