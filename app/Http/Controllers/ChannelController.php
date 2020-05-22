<?php

namespace App\Http\Controllers;

use App\Channel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ChannelController extends Controller
{
    /**
     * Display a listing of statistics the resource.
     *
     * @param Channel $channel
     * @return Response
     */
    public function stats(Channel $channel)
    {
        $videos = $channel->videos()->get();
        $allFiles = Storage::allFiles('public/uploads/channel-' . $channel->id);
        $size = 0;
        foreach ($allFiles as $file) {
            $size += Storage::size($file);
        }

        return response([
            'message' => 'Stats from Channel #' . $channel->id,
            'stats' => [
                'likes' => $videos->sum('likes_count'),
                'views' => $videos->sum('views_count'),
                'comments' => $videos->sum('comments_count')
            ],
            'advanced_stats' => [
                'likes' => [
                    'max' => $videos->max('likes_count'),
                    'min' => $videos->min('likes_count'),
                    'avg' => $videos->avg('likes_count'),
                ],
                'views' => [
                    'max' => $videos->max('views_count'),
                    'min' => $videos->min('views_count'),
                    'avg' => $videos->avg('views_count')
                ],
                'comments' => [
                    'max' => $videos->max('comments_count'),
                    'min' => $videos->min('comments_count'),
                    'avg' => $videos->avg('comments_count')
                ]
            ],
            'storage_size' => $size
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Channel $channel
     * @return Response
     */
    public function show(Channel $channel)
    {
        $videos = $channel->videos;
        $likes = 0;
        $views = 0;
        $comments = 0;
        foreach ($videos as $video) {
            $likes += $video->Likes()->count();
            $views += $video->views_count;
            $comments += $video->comments()->count();
        }
        return response([
            'message' => 'Channel Found',
            'channel' => $channel,
            'stats' => [
                'likes' => $likes,
                'views' => $views,
                'comments' => $comments
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Channel $channel
     * @return Response
     */
    public function update(Request $request, Channel $channel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Channel $channel
     * @return Response
     */
    public function destroy(Channel $channel)
    {
        //
    }
}
