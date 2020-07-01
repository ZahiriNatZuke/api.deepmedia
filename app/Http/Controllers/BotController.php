<?php

namespace App\Http\Controllers;

use App\Bug;
use App\Suggestion;
use App\Tweak;
use App\User;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use League\CommonMark\Util\ArrayCollection;

class BotController extends Controller
{

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function storeBug(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|max:150|string',
            'topic' => ['required', Rule::in(['security', 'functionality', 'visual'])]
        ], [], [
            'body' => 'cuerpo del error',
            'topic' => 'asunto del error'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Bot Info',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $bug = new Bug($request->all());
        $bug->user_id = Auth::id();
        $bug->save();

        return response([], 201);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function storeSuggestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|max:150|string'
        ], [], [
            'body' => 'cuerpo del error'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Bot Info',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $sugg = new Suggestion($request->all());
        $sugg->user_id = Auth::id();
        $sugg->save();

        return response([], 201);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function findLastBug()
    {
        return response([
            'data' => Bug::query()->orderByDesc('created_at')->limit(1)->get()[0] ?? null
        ], 202);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function findLastSuggestion()
    {
        return response([
            'data' => Suggestion::query()->orderByDesc('created_at')->limit(1)->get()[0] ?? null
        ], 202);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function grantPermissionsToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_role' => ['required', Rule::in(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_ROOT'])],
            'user' => 'required|min:4|string|exists:users,username'
        ], [], [
            'new_role' => 'nuevo rol',
            'user' => 'usuario'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Bot Info',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        try {
            $user = User::query()->where('username', 'LIKE', '%' . $request->get('user') . '%')
                ->with('record')->firstOrFail();
        } catch (\Exception $exception) {
            return response([
                'status' => false,
                'message' => 'El usuario solicitado no está en mis registros.'
            ], 202);
        }

        $user->record()->update([
            'role' => $request->get('new_role')
        ]);

        return response([
            'status' => true
        ], 202);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function revokeAccessToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|min:4|string|exists:users,username',
            'why' => 'required|string|max:150',
            'days' => 'required|digits:1,2'
        ], [], [
            'user' => 'usuario',
            'why' => 'por qué',
            'days' => 'días'
        ]);

        try {
            $user = User::query()->where('username', 'LIKE', '%' . $request->get('user') . '%')
                ->with('record')->firstOrFail();
        } catch (\Exception $exception) {
            return response([
                'status' => false,
                'message' => 'El usuario solicitado no está en mis registros.'
            ], 202);
        }

        if ($validator->fails()) {
            return response([
                'from' => 'Bot Info',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $days = $request->get('days');
        settype($days, 'integer');

        $payload = $payload = array(
            'sub' => Crypt::encrypt(now()->addDays($days)->unix()),
            'iat' => now()->unix(),
            'nbf' => now()->addMillisecond()->unix(),
            'exp' => now()->addDays($days)->unix(),
            'user' => $user
        );

        $ban = [
            'status' => true,
            'user' => $user->username,
            'why' => $request->get('why'),
            'byWho' => Auth::user()->username,
            'banish_expired_at' => now()->addDays($days)->unix(),
            'hash' => JWT::encode($payload, env('APP_KEY'), 'HS512')
        ];

        $user->record()->update(['banished' => $ban]);

        $black_list_ip = Tweak::query()->where('option', 'LIKE', 'black_list_ip')
            ->firstOrFail()->value;
        $black_list_user = Tweak::query()->where('option', 'LIKE', 'black_list_user')
            ->firstOrFail()->value;

        foreach ($user->record->ip_list as $ip) {
            if (!in_array($ip, $black_list_ip, true))
                $black_list_ip[count($black_list_ip)] = $ip;
        }

        if (!in_array($user->id, $black_list_user, true))
            $black_list_user[count($black_list_user)] = $user->id;

        Tweak::query()->where('option', 'LIKE', 'black_list_ip')
            ->first()->update([
                'value' => $black_list_ip
            ]);

        Tweak::query()->where('option', 'LIKE', 'black_list_user')
            ->first()->update([
                'value' => $black_list_user
            ]);

        return response([
            'status' => true
        ], 202);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function grantAccessToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|min:4|string|exists:users,username'
        ], [], [
            'user' => 'usuario'
        ]);

        if ($validator->fails()) {
            return response([
                'status' => false,
                'message' => 'El usuario solicitado no está en mis registros.'
            ], 202);
        }

        try {
            $user = User::query()->where('username', 'LIKE', '%' . $request->get('user') . '%')
                ->with('record')->firstOrFail();
        } catch (\Exception $exception) {
            return response([
                'status' => false,
                'message' => 'El usuario solicitado no está en mis registros.'
            ], 202);
        }

        $this->eraseBanRecord($user,
            Tweak::query()->where('option', 'LIKE', 'black_list_user')->firstOrFail()->value,
            Tweak::query()->where('option', 'LIKE', 'black_list_ip')->firstOrFail()->value);

        return response([
            'status' => true
        ], 202);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function checkBanFromUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|min:4|string|exists:users,username'
        ], [], [
            'user' => 'usuario'
        ]);

        if ($validator->fails()) {
            return response([
                'status' => false,
                'message' => 'El usuario solicitado no está en mis registros.'
            ], 202);
        }

        try {
            $user = User::query()->where('username', 'LIKE', '%' . $request->get('user') . '%')
                ->with('record')->firstOrFail();
        } catch (\Exception $exception) {
            return response([
                'status' => false,
                'message' => 'El usuario solicitado no está en mis registros.'
            ], 202);
        }

        return response([
            'status' => $user->record->banished['status'],
            'data' => $user->record->banished
        ], 200);

    }

    /**
     * @param $user
     * @param $black_list_user
     * @param $black_list_ip
     */
    public function eraseBanRecord($user, $black_list_user, $black_list_ip)
    {
        $black_list_ip = new ArrayCollection($black_list_ip);
        $black_list_user = new ArrayCollection($black_list_user);

        $user->record()->update([
            'banished' => [
                'status' => false,
                'why' => '',
                'byWho' => '',
                'banish_expired_at' => ''
            ]
        ]);

        foreach ($user->record->ip_list as $ip) {
            if ($black_list_ip->contains($ip)) {
                $black_list_ip->remove($black_list_ip->indexOf($ip));
            }
        }

        if ($black_list_user->contains($user->id))
            $black_list_user->remove($black_list_user->indexOf($user->id));

        Tweak::query()->where('option', 'LIKE', 'black_list_ip')
            ->first()->update([
                'value' => $black_list_ip->toArray()
            ]);

        Tweak::query()->where('option', 'LIKE', 'black_list_user')
            ->first()->update([
                'value' => $black_list_user->toArray()
            ]);
    }

}
