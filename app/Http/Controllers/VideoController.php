<?php

namespace App\Http\Controllers;

use App\Video;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $videos = Video::query()
            ->where('category', 'LIKE', $category)
            ->where('state', 'LIKE', 'Public')
            ->orderByDesc('created_at')
            ->without('comments')
            ->paginate(6);

        return response([
            'videos' => $videos
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
        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:videos|string',
            'description' => 'required|max:255|string',
            'state' => ['required', 'string', Rule::in(['Public', 'Private'])],
            'category' => ['required', 'string', Rule::in(['Gameplay', 'Musical', 'Joke', 'Interesting', 'Tech', 'Tutorial'])],
            'poster' => 'required|image|max:10240|file',
            'video' => 'required|mimetypes:video/mp4,video/avi,video/x-matroska|max:307200|file',
            'duration' => 'required|numeric',
            'type' => ['required', 'string', Rule::in(['video/mp4', 'video/avi', 'video/x-matroska'])]
        ], [], [
            'title' => 'titulo',
            'description' => 'descripción',
            'state' => 'estado',
            'category' => 'categoría',
            'duration' => 'duración',
            'type' => 'tipo'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Info Video',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $fromRequestVideo = $request->all();
        $filePoster = request()->file('poster');
        $fileVideo = request()->file('video');
        $fromRequestVideo['poster'] = $filePoster->hashName();
        $fromRequestVideo['video'] = $fileVideo->hashName();
        $newVideo = new Video($fromRequestVideo);
        $newVideo->channel_id = Auth::id();

        $newVideo->save();

        Storage::put('public/uploads/channel-' . Auth::id() . '/video-' . $newVideo->id . '/poster/', $filePoster);
        Storage::put('public/uploads/channel-' . Auth::id() . '/video-' . $newVideo->id . '/video/', $fileVideo);
        return response([
            'video' => Video::query()->find($newVideo->id)
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
        try {
            $video = Video::query()->findOrFail($video);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        $video = Cache::remember('video-' . $video->id, now()->addSeconds(30), function () use ($video) {
            return $video;
        });
        return response([
            'video' => $video
        ], 200);
    }

    /**
     * Make View for Video
     * @param $video
     * @return Response
     */
    public function makeView($video)
    {
        try {
            Video::query()->findOrFail($video)->increment('views_count', 1);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        return response([], 200);
    }

    /**
     * Display a listing of statistics the resource.
     *
     * @param $video
     * @return Response
     */
    public function stats($video)
    {
        try {
            $video = Video::query()->findOrFail($video);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        return response([
            'stats' => [
                'likes' => $video->Likes()->count(),
                'views' => $video->views_count,
                'comments' => $video->comments()->count()
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param $video
     * @param Request $request
     * @return Response
     */
    public function update($video, Request $request)
    {
        try {
            $video = Video::query()->findOrFail($video);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['nullable', 'string', Rule::unique('videos')->ignore($video->id)],
            'description' => 'nullable|max:255|string',
            'state' => ['nullable', 'string', Rule::in(['Public', 'Private'])],
            'category' => ['nullable', 'string', Rule::in(['Gameplay', 'Musical', 'Joke', 'Interesting', 'Tech', 'Tutorial'])],
            'poster' => 'nullable|image|max:10240|file',
            'video' => 'nullable|mimetypes:video/mp4,video/avi,video/x-matroska|max:307200|file',
            'duration' => 'nullable|numeric',
            'type' => ['nullable', Rule::in(['video/mp4', 'video/avi', 'video/x-matroska'])]
        ], [], [
            'title' => 'titulo',
            'description' => 'descripción',
            'state' => 'estado',
            'category' => 'categoría',
            'duration' => 'duración',
            'type' => 'tipo'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Info Video',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $data = $request->all();

        if (request()->hasFile('video')) {
            Storage::delete('public/uploads/channel-' . $video->channel_id . '/video-' . $video->id . '/video/' . $video->video['name']);
            $fileVideo = request()->file('video');
            Storage::put('public/uploads/channel-' . $video->channel_id . '/video-' . $video->id . '/video/', $fileVideo);
            $data['video'] = $fileVideo->hashName();
        }

        if (request()->hasFile('poster')) {
            Storage::delete('public/uploads/channel-' . $video->channel_id . '/video-' . $video->id . '/poster/' . $video->poster['name']);
            $filePoster = request()->file('poster');
            Storage::put('public/uploads/channel-' . $video->channel_id . '/video-' . $video->id . '/poster/', $filePoster);
            $data['poster'] = $filePoster->hashName();
        }

        $video->update($data);

        return response([
            'video' => $video->refresh()
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
        try {
            $video = Video::query()->findOrFail($video);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        Storage::deleteDirectory('public/uploads/channel-' . $video->channel->id . '/video-' . $video->id);
        $video->delete();

        return response([], 200);
    }

    /**
     * Download Video
     * @param $video
     * @return BinaryFileResponse | Response
     */
    public function downloadVideo($video)
    {
        try {
            $video = Video::query()->findOrFail($video);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        $video->increment('downloads_count', 1);
        $FILE = storage_path('app\\public\\uploads\\channel-' . $video->channel_id . '\\video-' . $video->id . '\\video\\' . $video->video['name']);
        return response()->download($FILE, str_replace(' ', '_', $video->title));
    }

    /**
     * Check New Video
     * @param Request $request
     * @return Response
     */
    public function checkNewVideo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:videos|string',
            'description' => 'required|max:255|string',
            'state' => ['required', 'string', Rule::in(['Public', 'Private'])],
            'category' => ['required', 'string', Rule::in(['Gameplay', 'Musical', 'Joke', 'Interesting', 'Tech', 'Tutorial'])],
        ], [], [
            'title' => 'titulo',
            'description' => 'descripción',
            'state' => 'estado',
            'category' => 'categoría'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Info Video',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        return response([], 200);
    }
}
