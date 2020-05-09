<?php

namespace App\Http\Controllers;

use App\Video;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuxController extends Controller
{
    /**
     * Giving a like for video
     *
     * @param Video $video
     * @return mixed
     */
    public function like(Video $video)
    {
        $result = auth()->user()->myLikes()->toggle($video);
        if ($result['attached'] == []) {
            return response([
                'message' => 'Dislike',
                'status' => false
            ], 200);
        } else {
            return response([
                'message' => 'Like',
                'status' => true
            ], 200);
        }
    }

    /**
     * Making a favorite video
     *
     * @param Video $video
     * @return mixed
     */
    public function favorite(Video $video)
    {
        $result = auth()->user()->channel->myFavorites()->toggle($video);
        if ($result['attached'] == []) {
            return response([
                'message' => 'No-Favorite',
                'status' => false
            ], 200);
        } else {
            return response([
                'message' => 'Favorite',
                'status' => true
            ], 200);
        }
    }

    /**
     * Get a list of favorite videos
     *
     * @return Response
     */
    public function favorite_user()
    {
        $videos_id = auth()->user()->myLikes()->pluck('videos.id');
        $videos = Video::query()
            ->whereIn('id', $videos_id)
            ->with('channel')
            ->without('comments')
            ->latest()
            ->get();
        return response([
            'message' => 'Favorites Videos of User #' . Auth::id(),
            'videos' => $videos
        ], 200);
    }

}
