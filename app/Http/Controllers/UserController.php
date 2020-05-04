<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    /**
     * Handle an authentication attempt.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            return response([
                'message' => 'User Authenticated',
                'auth:check' => Auth::check(),
                'auth:user' => Auth::user(),
                'credentials' => $credentials
            ], 200);
        } else {
            return response([
                'message' => 'User Not Authenticated',
                'auth:check' => Auth::check(),
                'auth:user' => Auth::user(),
                'credentials' => $credentials
            ], 403);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserRequest $request
     * @return Response
     */
    public function store(UserRequest $request)
    {
        $fromUserRequest = $request->all();
        $newUser = new User($fromUserRequest);
        $newUser['ip_list'] = ['ip_list' => [$newUser['ip_list']]];
        $newUser['password'] = Hash::make($newUser['password']);
        $newUser->save();
        return response([
            'message' => 'User Stored',
            'user' => $newUser
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return Response
     */
    public function show(User $user)
    {
        $user['channel'] = $user->channel;
        return response([
            'message' => 'User Found',
            'user' => $user
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return Response
     */
    public function destroy(User $user)
    {
        //
    }
}
