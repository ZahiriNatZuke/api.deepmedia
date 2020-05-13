<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Http\Requests\UserUpdateRequest;
use App\Session;
use App\Video;
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
                'exp' => now()->addDays(2)->unix(),
                'user' => Auth::user()->channel
            );
            $encoded = JWT::encode($payload, env('APP_KEY'), 'HS512');
            $decoded = JWT::decode($encoded, env('APP_KEY'), array('HS512'));

            $refresh = JWT::encode(array(
                'sub' => Auth::id(),
                'iat' => now()->unix(),
                'nbf' => now()->addMillisecond()->unix(),
                'exp' => now()->addDays(14)->unix(),
            ), env('APP_KEY'));

            $new_session = new Session([
                'user_id' => Auth::id(),
                'jwt_refresh' => $refresh
            ]);

            $new_session->save();

            return response([
                'auth:message' => 'User Authenticated',
                'auth:user' => $decoded,
                'X-Authentication-JWT' => $encoded,
                'X-Refresh-JWT' => $refresh,
                'X-Encode-ID' => Crypt::encrypt(Auth::id())
            ], 200);

        } else {
            return response([
                'message' => 'User Not Authenticated',
            ], 422);
        }
    }

    /**
     * Handle an logout attempt.
     *
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request)
    {
        $jwt_refresh = $request->header('X-Refresh-JWT');
        try {
            JWT::decode($jwt_refresh, env('APP_KEY'), array('HS256'));
        } catch (\Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 401);
        }
        Session::query()->where('jwt_refresh', 'LIKE', $jwt_refresh)->delete();
        Auth::logout();
        return response([
            'message' => 'User Logout Successfully'
        ], 200);
    }

    /**
     * Handle an logout attempt.
     *
     * @param Request $request
     * @return Response
     */
    public function refresh(Request $request)
    {
        $jwt_refresh = $request->header('X-Refresh-JWT');
        try {
            $jwt_refresh_decoded = JWT::decode($jwt_refresh, env('APP_KEY'), array('HS256'));
        } catch (\Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 401);
        }

        $session = Session::query()->where('jwt_refresh', 'LIKE', $jwt_refresh)->get()[0];
        if ($session) {
            Auth::loginUsingId($jwt_refresh_decoded->sub);
        } else {
            $session->delete();
            return response([
                'message' => 'JWT Refresh Invalid, Session Closed'
            ], 401);
        }

        $payload = array(
            'sub' => Auth::id(),
            'iat' => now()->unix(),
            'nbf' => now()->addMillisecond()->unix(),
            'exp' => now()->addDays(2)->unix(),
            'user' => Auth::user()->channel
        );

        $encoded = JWT::encode($payload, env('APP_KEY'), 'HS512');
        $decoded = JWT::decode($encoded, env('APP_KEY'), array('HS512'));

        $new_jwt_refresh = JWT::encode(array(
            'sub' => Auth::id(),
            'iat' => now()->unix(),
            'nbf' => now()->addMillisecond()->unix(),
            'exp' => now()->addDays(14)->unix(),
        ), env('APP_KEY'));

        $session->update([
            'jwt_refresh' => $new_jwt_refresh
        ]);

        return response([
            'message' => 'User Login Successfully',
            'auth:message' => 'User Authenticated',
            'auth:user' => $decoded,
            'X-Authentication-JWT' => $encoded,
            'X-Refresh-JWT' => $new_jwt_refresh,
            'X-Encode-ID' => Crypt::encrypt(Auth::id())
        ], 200);
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
        try {
            $newUser->save();
        } catch (Exception $e) {
            return response([
                'message' => 'ERROR!!, User Not Stored',
                'error:message' => $e->getMessage(),
                'error' => $e->getCode(),
            ], 422);
        }
        return response([
            'message' => 'User Stored'
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
            'channel' => $user->channel
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
        if (isset($request['password']) && is_string($request['password']))
            $request['password'] = Hash::make($request['password']);
        if (request()->file('avatar', null)) {
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
        try {
            Session::query()->where('user_id', 'LIKE', $user->id)->delete();
            Video::query()->where('channel_id', 'LIKE', $user->channel->id)->delete();
            Comment::query()->where('user_id', 'LIKE', $user->id)->delete();
            $user->channel()->delete();
            $user->record()->delete();
            $user->delete();
        } catch (Exception $e) {
            return response([
                'message' => 'ERROR!!, User not Deleted',
                'error:message' => $e->getMessage(),
                'error' => $e->getCode(),
            ], 422);
        }
        return response([
            'message' => 'User Deleted'
        ], 200);
    }
}
