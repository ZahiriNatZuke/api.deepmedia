<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Http\Requests\CommentRequest;
use App\Video;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

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
        $newComment->user_id = Auth::id();
        $newComment->video_id = $video->id;
        $newComment->save();
        return response([
            'message' => 'Comment Stored for Video #' . $video->id,
            'comment' => $newComment
        ], 200);
    }
}
