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
                'downloads' => $videos->sum('downloads_count')
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
                'downloads' => [
                    'max' => $videos->max('downloads_count'),
                    'min' => $videos->min('downloads_count'),
                    'avg' => $videos->avg('downloads_count')
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
        $channel['videos'] = $channel->videos()->get();
        return response([
            'message' => 'Channel Found',
            'channel' => $channel
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
