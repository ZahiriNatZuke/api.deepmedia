<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoRequest;
use App\Http\Requests\VideoUpdateRequest;
use App\Video;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param $category
     * @return Response
     */
    public function index($category)
    {
        $videos = Cache::remember('videos-' . $category, now()->addSeconds(30), function () use ($category) {
            return Video::query()
                ->where('category', 'LIKE', $category)
                ->where('state', 'LIKE', 'Public')
                ->orderByDesc('created_at')
                ->without('comments')
                ->with('channel')
                ->get();
        });
        return response([
            'message' => 'All Videos by ' . $category,
            'category' => $category,
            'videos' => $videos
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param VideoRequest $request
     * @return Response
     */
    public function store(VideoRequest $request)
    {
        $fromRequestVideo = $request->all();
        $filePoster = request()->file('poster');
        $fileVideo = request()->file('video');
        $fromRequestVideo['poster'] = $filePoster->hashName();
        $fromRequestVideo['video'] = $fileVideo->hashName();
        $newVideo = new Video($fromRequestVideo);
        $newVideo->channel_id = Auth::user()->channel->id;
        try {
            $newVideo->save();
        } catch (Exception $e) {
            return response([
                'message' => 'ERROR!!, Video Not Stored',
                'error:message' => $e->getMessage(),
                'error' => $e->getCode(),
            ], 422);
        }
        Storage::put('public/uploads/channel-' . Auth::id() . '/video-' . $newVideo->id . '/poster/', $filePoster);
        Storage::put('public/uploads/channel-' . Auth::id() . '/video-' . $newVideo->id . '/video/', $fileVideo);
        return response([
            'message' => 'Video Stored',
            'video' => $newVideo
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Video $video
     * @return Response
     */
    public function show(Video $video)
    {
        $video->views_count = $video->views_count + 1;
        $video->update();
        $video['channel'] = $video->channel;
        $cachedVideo = Cache::remember('videos-' . $video->id, now()->addSeconds(30), function () use ($video) {
            return $video;
        });
        return response([
            'message' => 'Video Found',
            'video' => $cachedVideo
        ], 200);
    }

    /**
     * Display a listing of statistics the resource.
     *
     * @param Video $video
     * @return Response
     */
    public function stats(Video $video)
    {
        $likes = $video->Likes()->count();
        $views = $video->views_count;
        $comments = $video->comments()->count();
        return response([
            'message' => 'Stats from Channel #' . $video->id,
            'stats' => [
                'likes' => $likes,
                'views' => $views,
                'comments' => $comments
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Video $video
     * @param VideoUpdateRequest $request
     * @return Response
     */
    public function update(Video $video, VideoUpdateRequest $request)
    {
        $data = $request->all();

        if (request()->file('video')) {
            Storage::delete('public/uploads/channel-' . $video->channel_id . '/video-' . $video->id . '/video/' . $video->video['name']);
            $fileVideo = request()->file('video');
            Storage::put('public/uploads/channel-' . $video->channel_id . '/video-' . $video->id . '/video/', $fileVideo);
            $data['video'] = $fileVideo->hashName();
        }

        if (request()->file('poster')) {
            Storage::delete('public/uploads/channel-' . $video->channel_id . '/video-' . $video->id . '/poster/' . $video->poster['name']);
            $filePoster = request()->file('poster');
            Storage::put('public/uploads/channel-' . $video->channel_id . '/video-' . $video->id . '/poster/', $filePoster);
            $data['poster'] = $filePoster->hashName();
        }

        try {
            $video->update($data);
        } catch (Exception $e) {
            return response([
                'message' => 'ERROR!!, Video Not Updated',
                'error:message' => $e->getMessage(),
                'error' => $e->getCode(),
            ], 422);
        }
        return response([
            'message' => 'Video Updated',
            'video' => $video
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Video $video
     * @return Response
     * @throws Exception
     */
    public function destroy(Video $video)
    {
        try {
            Storage::deleteDirectory('public/uploads/channel-' . $video->channel->id . '/video-' . $video->id);
            $video->delete();
        } catch (Exception $e) {
            return response([
                'message' => 'ERROR!!, Video Not Deleted',
                'error:message' => $e->getMessage(),
                'error' => $e->getCode(),
            ], 422);
        }
        return response([
            'message' => 'Video Deleted',
            'video-deleted' => $video
        ], 200);
    }
}
