<?php

namespace App\Http\Controllers;

use App\Bug;
use App\Suggestion;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BotController extends Controller
{
    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function storeBug(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|max:150',
            'topic' => ['required', Rule::in(['security', 'functionality', 'visual'])]
        ], [], [
            'body' => 'cuerpo del error',
            'topic' => 'asunto del error'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Bot Info',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $bug = new Bug($request->all());
        $bug->user_id = Auth::id();
        $bug->save();

        return response([
            'bug' => $bug
        ], 201);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function storeSuggestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|max:150'
        ], [], [
            'body' => 'cuerpo del error'
        ]);

        if ($validator->fails()) {
            return response([
                'from' => 'Bot Info',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $sugg = new Suggestion($request->all());
        $sugg->user_id = Auth::id();
        $sugg->save();

        return response([
            'suggestion' => $sugg
        ], 201);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function findLastBug()
    {
        return response([
            'data' => Bug::query()->orderByDesc('created_at')->limit(1)->get()[0] ?? null
        ], 202);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function findLastSuggestion()
    {
        return response([
            'data' => Suggestion::query()->orderByDesc('created_at')->limit(1)->get()[0] ?? null
        ], 202);
    }
}
