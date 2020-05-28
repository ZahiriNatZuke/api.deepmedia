<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Http\Requests\NewPasswordRequest;
use App\Http\Requests\UserRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Session;
use App\User;
use App\Video;
use Exception;
use Faker\Generator as Faker;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
            'message' => 'User Stored',
            'user_id' => $newUser->refresh()->id
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
            'user' => $user->refresh()->channel
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

    /**
     * Change the current password from a user
     * @param NewPasswordRequest $request
     * @return Response
     */
    public function newPassword(NewPasswordRequest $request)
    {
        $fromRequest = $request->all();
        if (!Hash::check($fromRequest['current_password'], Auth::user()->getAuthPassword())) {
            return response([
                'message' => 'La Contraseña no es Correcta'
            ], 422);
        }
        User::query()->find(Auth::id())->update([
            'password' => Hash::make($fromRequest['new_password'])
        ]);

        return response([
            'message' => 'Contraseña Actualizada'
        ], 200);
    }

    /**
     * Retrieve link for Secret List
     * @param Request $request
     * @param Faker $faker
     * @return Response
     */
    public function secretList(Request $request, Faker $faker)
    {
        $jwt_temp = $request->header('X-TEMP-JWT');
        try {
            JWT::decode($jwt_temp, env('APP_KEY'), array('HS512'));
        } catch (\Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 401);
        }
        return response([
            'message' => 'Secret List',
            'secret_list' => [
                $faker->state,
                $faker->state,
                $faker->country,
                $faker->country,
                $faker->state,
                $faker->state,
                $faker->country,
                $faker->country,
                $faker->state,
                $faker->country
            ]
        ], 200);
    }

    /**
     * Check if new User is OK
     * @param UserRequest $request
     * @return Response
     */
    public function checkNewUser(UserRequest $request)
    {
        return response([
            'message' => 'Ready for Store'
        ], 200);
    }
}
