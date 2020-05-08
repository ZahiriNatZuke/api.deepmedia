<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Http\Requests\CommentRequest;
use App\Video;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class CommentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param $video
     * @param CommentRequest $request
     * @return Response
     */
    public function store($video, CommentRequest $request)
    {
        $video_id = $video;
        $video = Video::query()->findOrFail($video);
        $fromRequestComment = $request->all();
        $newComment = new Comment($fromRequestComment);
        $user_id = Crypt::decrypt(Auth::id());
        $newComment->user_id = $user_id;
        $newComment->video_id = $video_id;
        $newComment->save();
        return response([
            'message' => 'Comment Stored for Video #' . $video_id,
            'comment' => $newComment
        ], 200);
    }
}
