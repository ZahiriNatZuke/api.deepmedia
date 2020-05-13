<?php

namespace App\Http\Middleware;

use App\Record;
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
            ], 401);
        }
        try {
            $id = Crypt::decrypt($request->header('X-Encode-ID'));
        } catch (DecryptException $e) {
            return response([
                'message' => $e->getMessage(),
            ], 401);
        }

        Auth::loginUsingId($id);

        $this->updateIpList($id, $request);

        return $next($request);
    }

    private function updateIpList($id, Request $request)
    {
        $record = Record::query()->find($id);
        $ip_list = $record->ip_list;
        if (!in_array($request->getClientIp(), $ip_list)) {
            $ip_list[count($ip_list)] = $request->getClientIp();
            $record->update([
                'ip_list' => $ip_list
            ]);
        }
    }
}
