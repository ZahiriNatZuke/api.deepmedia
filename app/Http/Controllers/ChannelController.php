<?php

namespace App\Http\Controllers;

use App\Channel;
use App\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;

class ChannelController extends Controller
{
    /**
     * Display a listing of statistics the resource.
     *
     * @param $channel
     * @return Response
     */
    public function stats($channel)
    {
        $channel = Channel::query()->findOrFail($channel);
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
            'message' => 'Stats from Channel #' . Crypt::decrypt($channel->id),
            'stats' => [
                'likes' => $likes,
                'views' => $views,
                'comments' => $comments
            ]
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
     * @param $channel
     * @return Response
     */
    public function show($channel)
    {
        $channel = Channel::query()->findOrFail($channel);
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
