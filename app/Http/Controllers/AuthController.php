<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    /**
     * Get the JWT authentication guard.
     *
     * This guard is responsible for handling authentication
     * using JSON Web Tokens (JWT) for the API.
     *
     * @return JWTGuard
     */
    protected function guard(): JWTGuard
    {
        return auth('api');
    }

    /**
     * Authenticate a user and generate a JWT token.
     *
     * This method validates user credentials (email & password).
     * If the credentials are valid, a JWT token is generated and returned.
     * Otherwise, an unauthorized response is sent.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = $this->guard()->attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Retrieve the authenticated user's profile.
     *
     * This endpoint returns the currently authenticated user
     * based on the provided JWT token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        return response()->json($this->guard()->user());
    }

    /**
     * Log out the authenticated user.
     *
     * This method invalidates the current JWT token,
     * preventing it from being used for further requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Format and return the JWT token response.
     *
     * The response includes:
     * - the access token
     * - the token type
     * - the token expiration time (in seconds)
     * - the authenticated user data
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $this->guard()->factory()->getTTL() * 60,
            'user'         => $this->guard()->user()
        ]);
    }
}
