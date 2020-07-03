<?php

namespace App\Http\Middleware;

use App\Tweak;
use App\User;
use Closure;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\CommonMark\Util\ArrayCollection;

class SEC_GLOBAL
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ip_client = $request->getClientIp();
        $black_list_ip = Tweak::query()->where('option', 'LIKE', 'black_list_ip')
            ->firstOrFail()->value;
        $black_list_user = Tweak::query()->where('option', 'LIKE', 'black_list_user')
            ->firstOrFail()->value;

        $can_from_ip = !in_array($ip_client, $black_list_ip, true);
        $can_from_user = false;

        if ($request->hasHeader('X-Authentication-JWT')) {
            try {
                $decoded = JWT::decode($request->header('X-Authentication-JWT'), env('APP_KEY'), array('HS512'));
            } catch (\Exception $exception) {
                return response([
                    'from' => 'Info Seguridad',
                    'error_message' => 'Petición no Autorizada'
                ], 401);
            }

            try {
                $user = User::query()->with('record')->findOrFail($decoded->channel->id);
            } catch (\Exception $exception) {
                return response([
                    'from' => 'Info Seguridad',
                    'error_message' => 'Usuario no Autorizado'
                ], 401);
            }

            if (in_array($user->id, $black_list_user, true) || $user->record->banished['status'] === true)
                return $this->confirmUserForBan($user, $black_list_ip, $ip_client);
            else
                $can_from_user = true;
        } else {
            $can_from_user = true;
        }

        if ($request->hasHeader('X-Refresh-JWT')) {
            try {
                $jwt_refresh_decoded = JWT::decode($request->header('X-Refresh-JWT'), env('APP_KEY'), array('HS256'));
            } catch (\Exception $exception) {
                return response([
                    'from' => 'Info Sesión',
                    'error_message' => 'Sesión Comprometida'
                ], 401);
            }

            try {
                $user = User::query()->with('record')->findOrFail($jwt_refresh_decoded->sub);
            } catch (\Exception $exception) {
                return response([
                    'from' => 'Info Seguridad',
                    'error_message' => 'Usuario no Autorizado'
                ], 401);
            }

            if (in_array($user->id, $black_list_user, true) || $user->record->banished['status'] === true)
                return $this->confirmUserForBan($user, $black_list_ip, $ip_client);
            else
                $can_from_user = true;

        } else {
            $can_from_user = true;
        }

        if ($request->hasHeader('X-Banished')) {

            try {
                JWT::decode($request->header('X-Banished'), env('APP_KEY'), array('HS512'));
            } catch (\Exception $exception) {
                return response([
                    'from' => 'Info Seguridad',
                    'error_message' => 'Petición no Autorizada'
                ], 401);
            }

            try {
                $user = User::query()->where('username', 'LIKE', '%' . $request->get('user') . '%')
                    ->with('record')->firstOrFail();
            } catch (\Exception $exception) {
                return response([
                    'from' => 'Info Usuario',
                    'error_message' => 'El usuario solicitado no existe o no está disponible.'
                ], 403);
            }

            $banish_expired_at = $user->record->banished['banish_expired_at'];
            settype($banish_expired_at, 'integer');

            if ($user->record->banished['hash'] === $request->header('X-Banished') && $banish_expired_at <= now()->unix()) {

                $this->eraseBanRecord($user, $black_list_user, $black_list_ip);

                return response([
                    'from' => 'Info Seguridad',
                    'message' => 'El período de expulsión ha finalizado.'
                ], 200);

            } else
                return response([
                    'from' => 'Info Seguridad',
                    'error_message' => 'Usuario Baneado',
                    'banished' => $user->record->banished
                ], 403);

        }

        return ($can_from_ip && $can_from_user) ? $next($request) : response([
            'from' => 'Info Seguridad',
            'error_message' => 'Usuario Baneado',
            'banished' => [
                'status' => true,
                'why' => 'Violar el tiempo de Expulsión.',
                'byWho' => 'Sys_ROOT',
                'banish_expired_at' => now()->addWeek()->unix()
            ]
        ], 403);
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

    /**
     * @param $user
     * @param $black_list_ip
     * @param $ip_client
     * @return ResponseFactory|Response
     */
    public function confirmUserForBan($user, $black_list_ip, $ip_client)
    {
        foreach ($user->record->ip_list as $ip) {
            if (!in_array($ip, $black_list_ip, true))
                $black_list_ip[count($black_list_ip)] = $ip;
        }
        if (!in_array($ip_client, $black_list_ip, true))
            $black_list_ip[count($black_list_ip)] = $ip_client;

        Tweak::query()->where('option', 'LIKE', 'black_list_ip')
            ->first()->update(['value' => $black_list_ip]);

        return response([
            'from' => 'Info Seguridad',
            'error_message' => 'Usuario Baneado',
            'banished' => $user->record->banished
        ], 403);
    }

}
