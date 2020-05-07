<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Http\Requests\CommentRequest;
use App\Video;
use Illuminate\Http\Response;

class CommentController extends Controller
{
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
        //cambiar las aligación hardcore del user_id y poner el id del usuario autenticado
        $newComment->user_id = 1;
        $video->comments()->save($newComment);
        return response([
            'message' => 'Comment Stored for Video #' . $video->id,
            'comment' => $newComment
        ], 200);
    }
}
