<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class DecodeID
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param $parameter
     * @return mixed
     */
    public function handle($request, Closure $next, $parameter)
    {
        $value = $request->route()->parameter($parameter);
        try {
            $decoded = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            return response([
                'message' => $e->getMessage(),
            ], 403);
        }
        $request->route()->setParameter($parameter, $decoded);
        return $next($request);
    }
}
