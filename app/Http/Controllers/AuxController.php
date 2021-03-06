<?php

namespace App\Http\Controllers;

use App\Channel;
use App\User;
use App\Video;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuxController extends Controller
{
    /**
     * Giving a like for video
     *
     * @param $video
     * @return Response
     */
    public function like($video)
    {
        try {
            $video = Video::query()->findOrFail($video);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        $result = auth()->user()->myLikes()->toggle($video);
        if ($result['attached'] == []) {
            return response([
                'message' => 'Dislike',
                'status' => false,
                'likes' => $video->refresh()->Likes
            ], 200);
        } else {
            return response([
                'message' => 'Like',
                'status' => true,
                'likes' => $video->refresh()->Likes
            ], 200);
        }
    }

    /**
     * Making a favorite video
     *
     * @param Video $video
     * @return Response
     */
    public function favorite($video)
    {
        try {
            $video = Video::query()->findOrFail($video);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        $result = auth()->user()->channel->myFavorites()->toggle($video);
        if ($result['attached'] == []) {
            return response([
                'message' => 'No-Favorite',
                'status' => false,
                'favoriteForWho' => $video->refresh()->favoriteForWho
            ], 200);
        } else {
            return response([
                'message' => 'Favorite',
                'status' => true,
                'favoriteForWho' => $video->refresh()->favoriteForWho
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
        $videos_id = auth()->user()->channel->myFavorites()->pluck('videos.id');
        $videos = Video::query()
            ->whereIn('id', $videos_id)
            ->without('comments')
            ->latest()
            ->get();
        return response([
            'videos' => $videos
        ], 200);
    }

    /**
     * Get Count Video from Categories
     *
     * @return Response
     */
    public function countVideoByCategories()
    {
        $countGameplay = Video::query()->where('category', 'LIKE', 'Gameplay')->count();
        $countJoke = Video::query()->where('category', 'LIKE', 'Joke')->count();
        $countMusical = Video::query()->where('category', 'LIKE', 'Musical')->count();
        $countInteresting = Video::query()->where('category', 'LIKE', 'Interesting')->count();
        $countTech = Video::query()->where('category', 'LIKE', 'Tech')->count();
        $countTutorial = Video::query()->where('category', 'LIKE', 'Tutorial')->count();

        return response([
            'categories' => [
                'Gameplay' => [
                    'name' => 'gameplay',
                    'link' => 'gameplay',
                    'img' => 'gameplay.png',
                    'count_videos' => $countGameplay
                ],
                'Joke' => [
                    'name' => 'humor',
                    'link' => 'joke',
                    'img' => 'joke.jpg',
                    'count_videos' => $countJoke
                ],
                'Musical' => [
                    'name' => 'musical',
                    'link' => 'musical',
                    'img' => 'musical.jpg',
                    'count_videos' => $countMusical
                ],
                'Interesting' => [
                    'name' => 'interesantes',
                    'link' => 'interesting',
                    'img' => 'interesting.jpg',
                    'count_videos' => $countInteresting
                ],
                'Tech' => [
                    'name' => 'tecnología',
                    'link' => 'tech',
                    'img' => 'tech.jpg',
                    'count_videos' => $countTech
                ],
                'Tutorial' => [
                    'name' => 'tutoriales',
                    'link' => 'tutorial',
                    'img' => 'tutorial.png',
                    'count_videos' => $countTutorial
                ],
            ]
        ], 200);
    }

    /**
     * Get Top Video
     *
     * @return Response
     */
    public function topVideo()
    {
        $byViews = Cache::remember('byViews-' . now()->unix(), now()->addSeconds(30), function () {
            return Video::query()->orderByDesc('views_count')->take(5)->without('comments')->get();
        });
        $byLikes = Cache::remember('byLikes-' . now()->unix(), now()->addSeconds(30), function () {
            return Video::query()->orderByDesc('likes_count')->take(5)->without('comments')->get();
        });

        return response([
            'byViews' => $byViews,
            'byLikes' => $byLikes
        ], 200);
    }

    /**
     * Get Top Video by Channel
     *
     * @param $channel
     * @return Response
     */
    public function topVideoByChannel($channel)
    {
        try {
            $channel = Channel::query()->findOrFail($channel);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Canal',
                'error_message' => 'El canal solicitado no existe o no está disponible.'
            ], 404);
        }

        $byViews = Cache::remember('byViewsForChannel-' . $channel->id . now()->unix(), now()->addSeconds(30),
            function () use ($channel) {
                return Video::query()
                    ->where('channel_id', 'LIKE', $channel->id)
                    ->orderByDesc('views_count')
                    ->without('comments')
                    ->first();
            });

        $byLikes = Cache::remember('byLikesForChannel-' . $channel->id . now()->unix(), now()->addSeconds(30),
            function () use ($channel) {
                return Video::query()
                    ->where('channel_id', 'LIKE', $channel->id)
                    ->orderByDesc('likes_count')
                    ->without('comments')
                    ->first();
            });

        $byDownload = Cache::remember('byDownloadForChannel-' . $channel->id . now()->unix(), now()->addSeconds(30),
            function () use ($channel) {
                return Video::query()
                    ->where('channel_id', 'LIKE', $channel->id)
                    ->orderByDesc('downloads_count')
                    ->without('comments')
                    ->first();
            });

        return response([
            'byViews' => $byViews,
            'byLikes' => $byLikes,
            'byDownload' => $byDownload
        ], 200);
    }

    /**
     * Get PlayList
     * @param $video
     * @return Response
     */
    public function playList($video)
    {
        try {
            $video = Video::query()->findOrFail($video);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        $fromCategory = Video::query()
            ->where('id', 'NOT LIKE', $video->id)
            ->where('category', 'LIKE', $video->category)
            ->orderByDesc('likes_count')
            ->take(5);

        $fromAll = Video::query()
            ->where('id', 'NOT LIKE', $video->id)
            ->orderByDesc('views_count')
            ->take(10);

        $fromChannel = Video::query()
            ->where('id', 'NOT LIKE', $video->id)
            ->where('channel_id', 'LIKE', $video->channel_id)
            ->orderByDesc('created_at')
            ->take(5);

        $playList = Cache::remember('playList-' . now()->unix(), now()->addSeconds(30), function ()
        use ($fromAll, $fromCategory, $fromChannel, $video) {
            return Video::query()
                ->where('id', 'NOT LIKE', $video->id)
                ->where('state', 'LIKE', 'Public')
                ->union($fromCategory->getQuery())
                ->union($fromAll->getQuery())
                ->union($fromChannel->getQuery())
                ->distinct()
                ->orderByDesc('views_count')
                ->limit(20)
                ->get();
        });
        return response([
            'playlist' => $playList
        ], 200);
    }

    /**
     * Make a Query Search on DB
     * @param $query
     * @return Response
     */
    public function search($query)
    {
        $users = User::query()
            ->where('fullname', 'LIKE', '%' . $query . '%')
            ->orWhere('username', 'LIKE', '%' . $query . '%')
            ->with('channel')
            ->orderBy('username')
            ->get();

        $videos = Video::query()
            ->where('state', 'LIKE', 'Public')
            ->where('title', 'LIKE', '%' . $query . '%')
            ->orWhere('description', 'LIKE', '%' . $query . '%')
            ->orWhere('category', 'LIKE', '%' . $query . '%')
            ->orderBy('title')
            ->get();

        return response([
            'users' => $users,
            'videos' => $videos
        ], 200);
    }

    /**
     *Get A JWT, Temporal Access Granted
     * @param Request $request
     * @return Response
     */
    public function tempJWT(Request $request)
    {
        $payload = array(
            'sub' => Auth::id(),
            'iat' => now()->unix(),
            'nbf' => now()->addMillisecond()->unix(),
            'exp' => now()->addHour()->unix(),
            'finger_print' => $request->fingerprint()
        );
        return response([

        ], 200, [
            'X-Temp-JWT' => JWT::encode($payload, env('APP_KEY'), 'HS512')
        ]);
    }

    /**
     * Get 3 random numbers between 0-9
     */
    public function randomNumbers()
    {
        return response([
            'array_numbers' => array_rand([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 3)
        ], 200);
    }
}
