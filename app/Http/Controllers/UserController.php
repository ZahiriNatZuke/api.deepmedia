<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
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
            Auth::login(Auth::user());
            $payload = array(
                'sub' => rand(),
                'iss' => 'http://api.deepmedia.dev.com',
                'aud' => 'http://api.deepmedia.dev.com',
                'iat' => now()->unix(),
                'nbf' => now()->addMillisecond()->unix(),
                'exp' => now()->addDays(14)->unix(),
                'user' => Auth::user()
            );
            $encoded = JWT::encode($payload, env('APP_KEY'), 'HS512');
            $decoded = JWT::decode($encoded, env('APP_KEY'), array('HS512'));

            return response([
                'auth:message' => 'User Authenticated',
                'auth:user' => $decoded,
                'jwt' => $encoded
            ], 200);
        } else {
            return response([
                'message' => 'User Not Authenticated',
            ], 422);
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
