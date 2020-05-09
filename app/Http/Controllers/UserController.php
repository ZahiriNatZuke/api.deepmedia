<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserUpdateRequest;
use App\Session;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
                'sub' => Auth::id(),
                'iat' => now()->unix(),
                'nbf' => now()->addMillisecond()->unix(),
                'exp' => now()->addDays(1)->unix(),
                'user' => Auth::user()
            );
            $encoded = JWT::encode($payload, env('APP_KEY'), 'HS512');
            $decoded = JWT::decode($encoded, env('APP_KEY'), array('HS512'));

            $refresh = JWT::encode(array(
                'sub' => Auth::id(),
                'iat' => now()->unix(),
                'nbf' => now()->addMillisecond()->unix(),
                'exp' => now()->addDays(14)->unix(),
            ), env('APP_KEY'));

            $session = new Session(array(
                'user_id' => Auth::id(),
                'jwt_refresh' => $refresh,
                'last_activity' => now()->toDateTime()
            ));

            try {
                $session->saveOrFail();
            } catch (\Throwable $exception) {
                $session->update();
            }

            return response([
                'auth:message' => 'User Authenticated',
                'auth:user' => $decoded,
                'session' => $session
            ], 200)
                ->header('X-Authentication-JWT', $encoded, true)
                ->header('X-Refresh-JWT', $refresh, true)
                ->header('X-Encode-ID', Crypt::encrypt(Auth::id()), true);

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
     * @param UserUpdateRequest $request
     * @param User $user
     * @return Response
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $request['password'] = Hash::make($request['password']);
        if (request()->file('avatar')) {
            Storage::delete('public/uploads/channel-' . $user->channel->id . '/avatar/' . $user->channel->avatar['name']);
            $fileAvatar = request()->file('avatar');
            Storage::put('public/uploads/channel-' . $user->channel->id . '/avatar/', $fileAvatar);
            $user->channel()->update([
                'avatar' => $fileAvatar->hashName()
            ]);
        }
        $user->update($request->all());
        return response([
            'message' => 'User Updated',
            'user' => $user->channel
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return Response
     * @throws Exception
     */
    public function destroy(User $user)
    {
        Storage::deleteDirectory('public/uploads/channel-' . $user->channel->id);
        $user->channel()->delete();
        $user->session()->delete();
        $user->record()->delete();
        $user->delete();
        return response([
            'message' => 'User Deleted'
        ], 200);
    }
}
