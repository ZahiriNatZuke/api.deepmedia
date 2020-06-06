<?php

namespace App\Http\Controllers;

use App\Comment;
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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        $username = $request->get('username');

        try {
            $user = User::query()->where('username', 'LIKE', $username)->with('record')->firstOrFail();
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Login',
                'error_message' => 'Credenciales Incorrectas'
            ], 401);
        }

        $expire_time = $user->record->reset_password['password_expired_at'];
        if ((isset($expire_time) && now()->unix() <= $expire_time) || $expire_time === '') {

            $credentials = $request->only('username', 'password');

            if (Auth::attempt($credentials)) {
                Auth::login(Auth::user());
                $payload = array(
                    'sub' => Auth::id(),
                    'iat' => now()->unix(),
                    'nbf' => now()->addMillisecond()->unix(),
                    'exp' => now()->addDays(2)->unix(),
                    'channel' => Auth::user()->channel
                );
                $encoded = JWT::encode($payload, env('APP_KEY'), 'HS512');

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
                    'from' => 'Info Sesión',
                    'message' => 'Sesión Iniciada con Éxito',
                    'auth_user' => Auth::user()->channel,
                ], 200, [
                    'X-Authentication-JWT' => $encoded,
                    'X-Refresh-JWT' => $refresh,
                    'X-Encode-ID' => Crypt::encrypt(Auth::id())
                ]);

            } else {
                return response([
                    'from' => 'Info Login',
                    'error_message' => 'Credenciales Incorrectas'
                ], 401);
            }
        } else {
            return response([
                'from' => 'Info Login',
                'error_message' => 'Su Contraseña ha Expirado'
            ], 401);
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
                'from' => 'Info Sesión',
                'error_message' => 'Sesión Comprometida'
            ], 401);
        }
        Session::query()->where('jwt_refresh', 'LIKE', $jwt_refresh)->delete();
        Auth::logout();
        return response([
            'from' => 'Info Sesión',
            'message' => 'Sesión Cerrada con Éxito'
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
                'from' => 'Info Sesión',
                'error_message' => 'Sesión Comprometida'
            ], 401);
        }

        $session = Session::query()->where('jwt_refresh', 'LIKE', $jwt_refresh)->get()[0];
        if ($session) {
            Auth::loginUsingId($jwt_refresh_decoded->sub);
        } else {
            $session->delete();
            return response([
                'from' => 'Info Sesión',
                'error_message' => 'Seguridad Comprometida, Sesión Cerrada'
            ], 401);
        }

        $payload = array(
            'sub' => Auth::id(),
            'iat' => now()->unix(),
            'nbf' => now()->addMillisecond()->unix(),
            'exp' => now()->addDays(2)->unix(),
            'channel' => Auth::user()->channel
        );

        $encoded = JWT::encode($payload, env('APP_KEY'), 'HS512');

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
            'from' => 'Info Usuario',
            'message' => 'Sesión Recuperada',
            'auth_user' => Auth::user()->channel
        ], 200)->withHeaders([
            'X-Authentication-JWT' => $encoded,
            'X-Refresh-JWT' => $new_jwt_refresh,
            'X-Encode-ID' => Crypt::encrypt(Auth::id())
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string',
            'username' => 'required|unique:users|min:4|string',
            'email' => 'required|email:rfc,strict,spoof,filter|unique:users',
            'password' => 'required|min:8|confirmed'
        ], [], [
            'fullname' => 'nombre completo',
            'username' => 'usuario',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'password_confirmation' => 'confirmación de contraseña'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Info Usuario',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $newUser = new User($request->all());
        $newUser['password'] = Hash::make($newUser['password']);
        $newUser->save();

        return response([
            'from' => 'Info Usuario',
            'message' => 'Su Cuenta ha sido creada',
            'user_id' => $newUser->id
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param $user
     * @return Response
     */
    public function show($user)
    {
        try {
            $user = User::query()->findOrFail($user);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Usuario',
                'error_message' => 'El usuario solicitado no existe o no está disponible'
            ], 404);
        }
        return response([
            'channel' => $user->channel
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $user
     * @return Response
     */
    public function update(Request $request, $user)
    {
        try {
            $user = User::query()->findOrFail($user);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Usuario',
                'error_message' => 'El usuario solicitado no existe o no está disponible'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => 'nullable|string',
            'username' => ['nullable', 'min:4', 'string', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email:rfc,strict,spoof,filter', Rule::unique('users')->ignore($user->id)],
            'avatar' => 'nullable|file|image|max:10240'
        ], [], [
            'fullname' => 'nombre completo',
            'username' => 'usuario',
            'email' => 'correo electrónico',
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Info Usuario',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        if (request()->hasFile('avatar')) {
            Storage::delete('public/uploads/channel-' . $user->channel->id . '/avatar/' . $user->channel->avatar['name']);
            $fileAvatar = request()->file('avatar');
            Storage::put('public/uploads/channel-' . $user->channel->id . '/avatar/', $fileAvatar);
            $user->channel()->update([
                'avatar' => $fileAvatar->hashName()
            ]);
        }
        $user->update($request->all());
        return response([
            'user' => $user->refresh()->channel
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $user
     * @return Response
     * @throws Exception
     */
    public function destroy($user)
    {
        try {
            $user = User::query()->findOrFail($user);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Usuario',
                'error_message' => 'El usuario solicitado no existe o no está disponible'
            ], 404);
        }

        Storage::deleteDirectory('public/uploads/channel-' . $user->channel->id);

        Session::query()->where('user_id', 'LIKE', $user->id)->sharedLock()->delete();
        Video::query()->where('channel_id', 'LIKE', $user->channel->id)->sharedLock()->delete();
        Comment::query()->where('user_id', 'LIKE', $user->id)->sharedLock()->delete();
        $user->channel()->delete();
        $user->record()->delete();
        $user->delete();

        return response([
            'from' => 'Info Usuario',
            'message' => 'Usuario Eliminado con Éxito'
        ], 200);
    }

    /**
     * Change the current password from a user
     * @param Request $request
     * @param Faker $faker
     * @return Response
     */
    public function newPassword(Request $request, Faker $faker)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:8|password',
            'new_password' => 'required|min:8|confirmed'
        ], [], [
            'current_password' => 'contraseña actual',
            'new_password' => 'contraseña nueva',
            'new_password_confirmation' => 'confirmación de contraseña nueva',
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Info Usuario',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $user = User::query()->find(Auth::id());
        $user->record()->update([
            'reset_password' => [
                'secret_list' => $user->record->reset_password['secret_list'],
                'password' => $faker->password(8, 12),
                'password_expired_at' => ''
            ]
        ]);
        $user->update([
            'password' => Hash::make($request->get('current_password'))
        ]);
        return response([
            'from' => 'Info Usuario',
            'message' => 'Contraseña Actualizada. Inicie Sesión para actualizar los Cambios'
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
        $jwt_temp = $request->header('X-Temp-JWT');
        try {
            JWT::decode($jwt_temp, env('APP_KEY'), array('HS512'));
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Seguridad',
                'error_message' => 'Petición no Autorizada'
            ], 401);
        }
        return response([
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
     * @param Request $request
     * @return Response
     */
    public function checkNewUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string',
            'username' => 'required|unique:users|min:4|string',
            'email' => 'required|email:rfc,strict,spoof,filter|unique:users',
            'password' => 'required|min:8|confirmed'
        ], [], [
            'fullname' => 'nombre completo',
            'username' => 'usuario',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'password_confirmation' => 'confirmación de contraseña'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Info Usuario',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        return response([], 200);
    }

    /**
     * Reset Password
     * @param Request $request
     * @param Faker $faker
     * @return Response
     */
    public function resetPassword(Request $request, Faker $faker)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,strict,spoof,filter|exists:users',
            'array_numbers' => 'required',
            'first_word' => 'required',
            'second_word' => 'required',
            'third_word' => 'required'
        ], [], [
            'email' => 'correo electrónico',
            'array_numbers' => 'palabra secreta',
            'first_word' => 'palabra secreta',
            'second_word' => 'palabra secreta',
            'third_word' => 'palabra secreta'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Info',
                'errors' => $validator->errors()->all()
            ], 422);
        } else {
            $email = $request->get('email');

            $user = User::query()->where('email', 'LIKE', $email)->with('record')->first();

            $array_numbers = $request->get('array_numbers');
            $secret_list = $user->record->reset_password['secret_list'];
            if ($secret_list[$array_numbers[0]] === $request->get('first_word') &&
                $secret_list[$array_numbers[1]] === $request->get('second_word') &&
                $secret_list[$array_numbers[2]] === $request->get('third_word')) {

                $password = $user->record->reset_password['password'];

                $user->record()->update([
                    'reset_password' => [
                        'secret_list' => $secret_list,
                        'password' => $faker->password(8, 12),
                        'password_expired_at' => now()->addHour()->unix()
                    ]
                ]);

                $user->update([
                    'password' => Hash::make($password)
                ]);

                return response([
                    'new_password' => $password
                ], 200);

            } else {
                return response([
                    'from' => 'Info',
                    'errors' => ['Palabras Incorrectas, inténtalo otra vez']
                ], 422);
            }
        }

    }
}
