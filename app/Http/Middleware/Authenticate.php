<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "User not found"
                ], 404);
            }
        } catch (TokenExpiredException $e) {
            return response()->json([
                "code" => 401,
                "status" => false,
                "message" => "Token expired"
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                "code" => 401,
                "status" => false,
                "message" => "Token invalid"
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                "code" => 401,
                "status" => false,
                "message" => "Unauthorized: Token not found"
            ], 401);
        }

        $this->auth->guard($guard)->setUser($user);

        return $next($request);
    }
}
