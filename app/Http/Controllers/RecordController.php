<?php

namespace App\Http\Controllers;

use App\User;
use Faker\Generator as Faker;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class RecordController extends Controller
{
    /**
     * Store the Secret List from a User
     * @param $user
     * @param Request $request
     * @param Faker $faker
     * @return Response
     */
    public function storeSecretList($user, Request $request, Faker $faker)
    {
        try {
            $user = User::query()->findOrFail($user);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Usuario',
                'error_message' => 'El usuario solicitado no existe o no está disponible.'
            ], 404);
        }


        $validator = Validator::make($request->all(), [
            'secret_list' => 'required'
        ], [], [
            'secret_list' => 'lista secreta'
        ]);

        $jwt_temp = $request->header('X-Temp-JWT');
        try {
            JWT::decode($jwt_temp, env('APP_KEY'), array('HS512'));
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Seguridad',
                'error_message' => 'Petición no Autorizada'
            ], 401);
        }
        if (!$validator->fails())
            $user->record()->update([
                'reset_password' => [
                    'secret_list' => $request->get('secret_list'),
                    'password' => $faker->password(8, 12),
                    'password_expired_at' => ''
                ]
            ]);
        else
            return response([
                'from' => 'Info',
                'errors' => $validator->errors()
            ], 422);

        return response([
            'from' => 'Info Usuario',
            'message' => 'Su Lista Secreta ha sido registrada'
        ], 201);
    }
}
