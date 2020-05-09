<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JWT_GRANT
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
        if (Auth::check()) {
            $record = Auth::user()->record;
            if ($record->role == 'ROLE_USER') {
                switch ($parameter) {
                    case 'video':
                        $video_channel_id = $request->route()->parameter($parameter)->channel_id;
                        if ($video_channel_id != Auth::user()->channel->id)
                            return response([
                                'message' => 'User not Authorized'
                            ], 422);
                        break;
                    case 'user':
                        $user_id = $request->route()->parameter($parameter)->id;
                        if ($user_id != Auth::id())
                            return response([
                                'message' => 'User not Authorized'
                            ], 422);
                        break;
                    default:
                        return response([
                            'message' => 'User not Authorized'
                        ], 422);
                        break;
                }
            }
        } else {
            return response([
                'message' => 'User not Authenticated'
            ], 401);
        }
        return $next($request);
    }
}
