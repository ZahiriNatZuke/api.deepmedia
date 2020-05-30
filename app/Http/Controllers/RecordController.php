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
     * @param User $user
     * @param Request $request
     * @param Faker $faker
     * @return Response
     */
    public function storeSecretList(User $user, Request $request, Faker $faker)
    {
        $validator = Validator::make($request->all(), [
            'secret_list' => 'required'
        ]);

        $jwt_temp = $request->header('X-TEMP-JWT');
        try {
            JWT::decode($jwt_temp, env('APP_KEY'), array('HS512'));
        } catch (\Exception $exception) {
            return response([
                'message' => 'Petición no Autorizada',
                'error_message' => $exception->getMessage()
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
                'message' => 'Info Corrupta',
                'error_message' => $validator->errors()
            ], 422);

        return response([
            'message' => 'Secret List Stored',
        ], 201);
    }
}
