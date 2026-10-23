<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\Actions\LoginUser;
use Modules\Auth\Actions\SendPasswordResetCode;
use Modules\Auth\Actions\VerifyPasswordResetCode;
use Modules\Auth\Actions\ResetPassword;
use Modules\Auth\Http\Requests\StoreUserRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\VerifyResetCodeRequest;
use Modules\Auth\Http\Requests\ResetPasswordRequest;
use Modules\Auth\Transformers\UserResource;

class AuthController extends Controller
{
    /**
     * @group Authentication
     * @unauthenticated
     * 
     * Register User
     * 
     * Register a new user account and receive an authentication API token.
     * 
     * @bodyParam name string required The user's full name. Example: Jane Doe
     * @bodyParam email string required The user's unique email address. Example: jane@example.com
     * @bodyParam password string required The account password (min 8 characters). Example: secret123
     * 
     * @response 201 {
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Jane Doe",
     *       "email": "jane@example.com"
     *     },
     *     "token": "1|sanctum_token_string"
     *   },
     *   "message": "User registered successfully"
     * }
     * @response 422 status=422 scenario="Validation error" {
     *   "message": "The email has already been taken.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function register(StoreUserRequest $request, RegisterUser $registerUser)
    {
        return $registerUser($request->validated());
    }

    /**
     * @group Authentication
     * @unauthenticated
     * 
     * User Login
     * 
     * Authenticate with email and password to receive an API access token.
     * 
     * @bodyParam email string required The user's registered email address. Example: jane@example.com
     * @bodyParam password string required The account password. Example: secret123
     * 
     * @response 200 {
     *   "data": {
     *     "token": "1|sanctum_token_string"
     *   },
     *   "message": "Logged in successfully"
     * }
     * @response 401 status=401 scenario="Invalid credentials" {
     *   "message": "Invalid login credentials."
     * }
     */
    public function login(LoginRequest $request, LoginUser $loginUser)
    {
        return $loginUser($request->validated());
    }

    /**
     * @group Authentication
     * @unauthenticated
     * 
     * Forgot Password
     * 
     * Request a 6-digit password reset code sent to the registered email address. The code expires in 2 minutes.
     * 
     * @bodyParam email string required The user's registered email address. Example: jane@example.com
     * 
     * @response 200 {
     *   "message": "Password reset code has been sent to your email."
     * }
     * @response 422 status=422 scenario="Email not found" {
     *   "message": "We could not find an account with this email address.",
     *   "errors": {
     *     "email": ["We could not find an account with this email address."]
     *   }
     * }
     */
    public function forgotPassword(ForgotPasswordRequest $request, SendPasswordResetCode $sendResetCode)
    {
        return response()->json($sendResetCode($request->validated()));
    }

    /**
     * @group Authentication
     * @unauthenticated
     * 
     * Verify Reset Code
     * 
     * Verify the 6-digit code sent to the email address. Must be submitted within 2 minutes.
     * 
     * @bodyParam email string required The user's registered email address. Example: jane@example.com
     * @bodyParam code string required The 6-digit verification code. Example: 482910
     * 
     * @response 200 {
     *   "message": "Verification code confirmed. You may now reset your password."
     * }
     * @response 422 status=422 scenario="Invalid or expired code" {
     *   "message": "The verification code has expired. Please request a new one.",
     *   "errors": {
     *     "code": ["The verification code has expired. Please request a new one."]
     *   }
     * }
     */
    public function verifyResetCode(VerifyResetCodeRequest $request, VerifyPasswordResetCode $verifyResetCode)
    {
        return response()->json($verifyResetCode($request->validated()));
    }

    /**
     * @group Authentication
     * @unauthenticated
     * 
     * Reset Password
     * 
     * Set a new password after successful code verification.
     * 
     * @bodyParam email string required The user's registered email address. Example: jane@example.com
     * @bodyParam password string required The new password (min 8 characters). Example: newSecret123
     * @bodyParam password_confirmation string required Must match password. Example: newSecret123
     * 
     * @response 200 {
     *   "message": "Password has been reset successfully."
     * }
     * @response 422 status=422 scenario="Verification expired or mismatch" {
     *   "message": "Verification code was not verified or verification session has expired. Please request a new code.",
     *   "errors": {
     *     "email": ["Verification code was not verified or verification session has expired. Please request a new code."]
     *   }
     * }
     */
    public function resetPassword(ResetPasswordRequest $request, ResetPassword $resetPassword)
    {
        return response()->json($resetPassword($request->validated()));
    }

    /**
     * @group Authentication
     * @authenticated
     * 
     * Get Authenticated User
     * 
     * Retrieve details of the currently authenticated user.
     * 
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "created_at": "2026-09-24T12:00:00.000000Z"
     *   }
     * }
     * @response 401 status=401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     */
    public function me(Request $request)
    {
        return new UserResource($request->user()->load(['profile.avatar', 'profile.cover']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('auth::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('auth::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
