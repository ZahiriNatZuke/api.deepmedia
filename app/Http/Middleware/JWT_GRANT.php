<?php

namespace App\Http\Middleware;

use App\User;
use App\Video;
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
                        try {
                            $video_channel_id = Video::query()->findOrFail($request->route()->parameter($parameter))->channel_id;
                        } catch (\Exception $exception) {
                            return response([
                                'from' => 'Info Video',
                                'error_message' => 'El video solicitado no existe o no está disponible.'
                            ], 404);
                        }
                        if ($video_channel_id != Auth::user()->channel->id)
                            return response([
                                'from' => 'Info Seguridad',
                                'error_message' => 'Acción no Autorizada'
                            ], 403);
                        break;
                    case 'user':
                        try {
                            $user_id = User::query()->findOrFail($request->route()->parameter($parameter))->id;
                        } catch (\Exception $exception) {
                            return response([
                                'from' => 'Info Usuario',
                                'error_message' => 'El usuario solicitado no existe o no está disponible'
                            ], 404);
                        }
                        if ($user_id != Auth::id())
                            return response([
                                'from' => 'Info Seguridad',
                                'error_message' => 'Acción no Autorizada'
                            ], 403);
                        break;
                    default:
                        return response([
                            'from' => 'Info Seguridad',
                            'error_message' => 'Petición no Autorizada'
                        ], 403);
                        break;
                }
            }
        } else {
            return response([
                'from' => 'Info Seguridad',
                'error_message' => 'Usuario no Autenticado'
            ], 401);
        }
        return $next($request);
    }
}
