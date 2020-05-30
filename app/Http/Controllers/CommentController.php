<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Http\Requests\CommentRequest;
use App\Video;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CommentController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param Video $video
     * @return Response
     */
    public function index(Video $video)
    {
        return response([
            'message' => 'All Comments for Video #' . $video->id,
            'comments' => $video->comments()->with('user')->get()
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Video $video
     * @param CommentRequest $request
     * @return Response
     */
    public function store(Video $video, CommentRequest $request)
    {
        $fromRequestComment = $request->all();
        $newComment = new Comment($fromRequestComment);
        $newComment->user_id = Auth::id();
        $newComment->video_id = $video->id;
        try {
            $newComment->save();
        } catch (\Exception $e) {
            return response([
                'message' => 'Comentario no Guardado',
                'error_message' => $e->getMessage(),
            ], 422);
        }
        return response([
            'message' => 'Comment Stored for Video #' . $video->id,
            'comment' => $newComment
        ], 201);
    }
}
