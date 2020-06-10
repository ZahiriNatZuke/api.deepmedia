<?php

namespace App\Http\Controllers;

const SIZE_LIMIT = 5368709120;

use App\Channel;
use App\Video;
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
    public function stats($channel)
    {
        try {
            $channel = Channel::query()->findOrFail($channel);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Canal',
                'error_message' => 'El canal solicitado no existe o no está disponible.'
            ], 404);
        }

        $videos = $channel->videos()->get();
        $allFiles = Storage::allFiles('public/uploads/channel-' . $channel->id);
        $size = 0;
        foreach ($allFiles as $file) {
            $size += Storage::size($file);
        }

        return response([
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
     * Display the specified resource.
     *
     * @param $channel
     * @return Response
     */
    public function show($channel)
    {
        try {
            $channel = Channel::query()->findOrFail($channel);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Canal',
                'error_message' => 'El canal solicitado no existe o no está disponible.'
            ], 404);
        }

        $channel['videos'] = $channel->videos()->get();
        return response([
            'channel' => $channel
        ], 200);
    }

    /**
     * Get Storage Size from Channel
     * @param $channel
     * @param Request $request
     * @return Response
     */
    public function storageSizeFromChannel($channel, Request $request)
    {
        try {
            $channel = Channel::query()->findOrFail($channel);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Canal',
                'error_message' => 'El canal solicitado no existe o no está disponible.'
            ], 404);
        }


        if ($request->has('video_size'))
            $video_size = $request->get('video_size');
        else
            return response([
                'from' => 'Info Video',
                'error_message' => 'Datos Incompletos'
            ], 422);

        settype($video_size, 'int');
        $allFiles = Storage::allFiles('public/uploads/channel-' . $channel->id);

        return response([
            'storage_size_available' => SIZE_LIMIT - $this->storageSize($allFiles),
            'can_store' => $this->storageSize($allFiles) + $video_size <= SIZE_LIMIT
        ], 200);
    }

    /**
     * Get Storage Size from Channel
     * @param $channel
     * @param $video
     * @param Request $request
     * @return Response
     */
    public function canStoreNewVideo($channel, $video, Request $request)
    {
        try {
            $channel = Channel::query()->findOrFail($channel);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Canal',
                'error_message' => 'El canal solicitado no existe o no está disponible.'
            ], 404);
        }

        try {
            $video = Video::query()->findOrFail($video);
        } catch (\Exception $exception) {
            return response([
                'from' => 'Info Video',
                'error_message' => 'El video solicitado no existe o no está disponible.'
            ], 404);
        }

        if ($request->has('video_size'))
            $video_size = $request->get('video_size');
        else
            return response([
                'from' => 'Info Video',
                'error_message' => 'Datos Incompletos'
            ], 422);

        settype($video_size, 'int');
        $allFiles = Storage::allFiles('public/uploads/channel-' . $channel->id);

        return response([
            'storage_size_available' => SIZE_LIMIT - $this->storageSize($allFiles),
            'can_store' => ($this->storageSize($allFiles) - Storage::size('public/' . $video->video['path'])) + $video_size <= SIZE_LIMIT,
        ], 200);
    }

    /**
     * Get Storage Size
     * @param array $allFiles
     * @return int
     */
    public function storageSize(array $allFiles): int
    {
        $size = 0;
        foreach ($allFiles as $file) {
            $size += Storage::size($file);
        }
        return $size;
    }

}
