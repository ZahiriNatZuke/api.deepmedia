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
     * @param $video
     * @return Response
     */
    public function index($video)
    {
        $comments = Comment::query()
            ->where('video_id', 'LIKE', $video)
            ->orderByDesc('created_at')
            ->with('user')
            ->get();
        return response([
            'message' => 'All Comments for Video #' . $video,
            'comments' => $comments
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
                'message' => 'ERROR!!, Comment Not Stored',
                'error:message' => $e->getMessage(),
                'error' => $e->getCode(),
            ], 422);
        }
        return response([
            'message' => 'Comment Stored for Video #' . $video->id,
            'comment' => $newComment
        ], 200);
    }
}
