<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\Actions\LoginUser;
use Modules\Auth\Http\Requests\StoreUserRequest;
use Modules\Auth\Http\Requests\LoginRequest;
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
        return new UserResource($request->user());
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
