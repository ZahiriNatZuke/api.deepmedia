<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param $video
     * @return Response
     */
    public function index($video)
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
            'comments' => $video->comments()->with('user')->get()
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Video $video
     * @param Request $request
     * @return Response
     */
    public function store($video, Request $request)
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
            'body' => 'required|max:150'
        ], [], [
            'body' => 'cuerpo del comentario'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Info Comentario',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $newComment = new Comment($request->all());
        $newComment->user_id = Auth::id();
        $newComment->video_id = $video->id;
        $newComment->save();

        return response([
            'comment' => $newComment
        ], 201);
    }
}
