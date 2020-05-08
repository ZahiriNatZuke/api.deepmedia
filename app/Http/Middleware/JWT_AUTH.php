<?php

namespace App\Http\Middleware;

use App\Session;
use Closure;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class JWT_AUTH
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
        $jwt_auth = $request->header('X-Authentication-JWT');
        try {
            JWT::decode($jwt_auth, env('APP_KEY'), array('HS512'));
        } catch (\Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 403);
        }
        try {
            $id = Crypt::decrypt($request->header('X-Encode-ID'));
        } catch (DecryptException $e) {
            return response([
                'message' => $e->getMessage(),
            ], 403);
        }

        Auth::loginUsingId($id);

        $this->updateSession($id);

        $this->updateIpList(Auth::user(), $request);

        return $next($request);
    }

    private function updateSession($id)
    {
        $session = Session::query()->where('user_id', 'LIKE', $id)->get()[0];
        $session->update([
            'last_activity' => now()->toDateTime()
        ]);
    }

    private function updateIpList($user, Request $request)
    {
        $ip_list = $user->ip_list;
        if (!in_array($request->getClientIp(), $user->ip_list))
            $ip_list[count($ip_list)] = $request->getClientIp();
        $user->ip_list = $ip_list;
        $user->update();
    }
}
