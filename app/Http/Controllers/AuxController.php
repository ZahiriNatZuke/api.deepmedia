<?php

namespace App\Http\Controllers;

use App\Video;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use function foo\func;

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
        $videos_id = auth()->user()->channel->myFavorites()->pluck('videos.id');
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

    /**
     * Get Count Video from Categories
     *
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
            'message' => 'Count Videos by Categories',
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

}
