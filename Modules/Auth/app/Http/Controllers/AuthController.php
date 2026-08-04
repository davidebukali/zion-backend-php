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
     * Display a listing of the resource.
     */
    public function register(StoreUserRequest $request, RegisterUser $registerUser)
    {
        return $registerUser($request->validated());
    }

    /**
     * Handle user login and return an API token.
     */
    public function login(LoginRequest $request, LoginUser $loginUser)
    {
        return $loginUser($request->validated());
    }

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
