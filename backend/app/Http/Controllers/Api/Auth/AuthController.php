<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    /** POST /api/auth/login */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return $this->error('Invalid credentials.', 401);
            }
        }
        catch (JWTException $e) {
            return $this->error('Could not create token. Please try again.', 500);
        }

        /** @var User|null $user */
        $user = JWTAuth::user();

        if (!$user || !$user->is_active) {
            // Safely invalidate token if exists
            try {
                if ($token) {
                    JWTAuth::setToken($token)->invalidate();
                }
            }
            catch (\Exception $e) {
            }

            return $this->error('Account is deactivated. Contact administrator.', 403);
        }

        return $this->success([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'stores' => $user->stores()->pluck('stores.id')->toArray(),
            ],
        ]);
    }

    /** POST /api/auth/logout */
    public function logout(): JsonResponse
    {
        try {
            if ($token = JWTAuth::getToken()) {
                JWTAuth::invalidate($token);
            }
        }
        catch (JWTException $e) {
        // Token invalid/expired → already logged out
        }

        return $this->success(null, 'Logged out successfully.');
    }

    /** POST /api/auth/refresh */
    public function refresh(): JsonResponse
    {
        try {
            if (!$token = JWTAuth::getToken()) {
                return $this->error('Token not provided.', 400);
            }

            $newToken = JWTAuth::refresh($token);

        }
        catch (TokenExpiredException $e) {
            return $this->error('Refresh token has expired. Please log in again.', 401);
        }
        catch (TokenInvalidException $e) {
            return $this->error('Token is invalid.', 401);
        }
        catch (JWTException $e) {
            return $this->error('Could not refresh token.', 401);
        }

        return $this->success([
            'token' => $newToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    /** GET /api/auth/me */
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return $this->error('Unauthenticated.', 401);
        }

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'stores' => $user->stores()->pluck('stores.id')->toArray(),
        ]);
    }
}