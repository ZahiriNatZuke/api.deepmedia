<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoRequest;
use App\Video;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        $fromRequestVideo['channel_id'] = 1;
        $newVideo = new Video($fromRequestVideo);
        $newVideo->channel_id = 1;
        try {
            $newVideo->save();
        } catch (Exception $e) {
            return response([
                'message' => 'ERROR!!, Video Not Stored',
                'errormessage' => $e->getMessage(),
                'error' => $e->getCode(),
            ], 500);
        }
        Storage::put('public/uploads/channel-1/video-' . $newVideo->id . '/poster/', $filePoster);
        Storage::put('public/uploads/channel-1/video-' . $newVideo->id . '/video/', $fileVideo);
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

        $cachedVideo = Cache::remember('videos-' . $video->id, now()->addSeconds(30), function () use ($video) {
            $video['comments'] = $video->comments;
            return $video;
        });
        return response([
            'message' => 'Video Found',
            'video' => $cachedVideo
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Video $video
     * @return Response
     * @throws ValidationException
     */
    public function update(Video $video, Request $request)
    {
        $data = $request->all();

        if (request()->file('video')) {
            Storage::delete('public/uploads/channel-1/video-' . $video->id . '/video/' . $video->video);
            $fileVideo = request()->file('video');
            Storage::put('public/uploads/channel-1/video-' . $video->id . '/video/', $fileVideo);
            $data['video'] = $fileVideo->hashName();
        }

        if (request()->file('poster')) {
            Storage::delete('public/uploads/channel-1/video-' . $video->id . '/poster/' . $video->poster);
            $filePoster = request()->file('poster');
            Storage::put('public/uploads/channel-1/video-' . $video->id . '/poster/', $filePoster);
            $data['poster'] = $filePoster->hashName();
        }
        $video->update($data);
        return response([
            'message' => 'Video Updated',
            'video' => $video
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param video $video
     * @return Response
     * @throws Exception
     */
    public function destroy(Video $video)
    {
        $video->delete();
        Storage::delete('public/uploads/channel-1/video-' . $video->id . '/video/' . $video->video);
        Storage::delete('public/uploads/channel-1/video-' . $video->id . '/poster/' . $video->poster);
        return response([
            'message' => 'Video Deleted',
            'video-deleted' => $video
        ], 200);
    }
}
