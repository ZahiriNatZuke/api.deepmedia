<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoRequest;
use App\Http\Requests\VideoUpdateRequest;
use App\Video;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
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
        $newVideo = new Video($fromRequestVideo);
        //Cambiar la asignación hardcore del channel_id y poner el canal del user autenticado
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
     * @param $video
     * @return Response
     */
    public function show($video)
    {
        $video = Video::query()->findOrFail($video);
        $video->views_count = $video->views_count + 1;
        $video->update();
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
     * @param $video
     * @return Response
     */
    public function stats($video)
    {
        $video = Video::query()->findOrFail($video);
        $likes = $video->Likes()->count();
        $views = $video->views_count;
        $comments = $video->comments()->count();
        return response([
            'message' => 'Stats from Channel #' . Crypt::decrypt($video->id),
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
     * @param $video
     * @param VideoUpdateRequest $request
     * @return Response
     */
    public function update($video, VideoUpdateRequest $request)
    {
        $video = Video::query()->findOrFail($video);
        $data = $request->all();

        if (request()->file('video')) {
            Storage::delete('public/uploads/channel-' . Crypt::decrypt($video->channel_id) . '/video-' . Crypt::decrypt($video->id) . '/video/' . $video->video['name']);
            $fileVideo = request()->file('video');
            Storage::put('public/uploads/channel-' . Crypt::decrypt($video->channel_id) . '/video-' . Crypt::decrypt($video->id) . '/video/', $fileVideo);
            $data['video'] = $fileVideo->hashName();
        }

        if (request()->file('poster')) {
            Storage::delete('public/uploads/channel-' . Crypt::decrypt($video->channel_id) . '/video-' . Crypt::decrypt($video->id) . '/poster/' . $video->poster['name']);
            $filePoster = request()->file('poster');
            Storage::put('public/uploads/channel-' . Crypt::decrypt($video->channel_id) . '/video-' . Crypt::decrypt($video->id) . '/poster/', $filePoster);
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
     * @param $video
     * @return Response
     * @throws Exception
     */
    public function destroy($video)
    {
        $video = Video::query()->findOrFail($video);
        Storage::deleteDirectory('public/uploads/channel-' . Crypt::decrypt($video->channel_id) . '/video-' . Crypt::decrypt($video->id));
        $video->delete();
        return response([
            'message' => 'Video Deleted',
            'video-deleted' => $video
        ], 200);
    }
}
