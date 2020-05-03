<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthKey
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
        $token = $request->header('X-API-KEY');
//        if (!$token == 'ABCDEFGHIJK') {
//            return response(['message' => 'App Key Not Found'], 401);
//        }
        return $next($request);
    }
}
