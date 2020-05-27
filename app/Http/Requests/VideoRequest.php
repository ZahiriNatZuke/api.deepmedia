<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|unique:videos|string',
            'description' => 'required|max:255|string',
            'state' => ['required', 'string', Rule::in(['Public', 'Private'])],
            'category' => ['required', 'string', Rule::in(['Gameplay', 'Musical', 'Joke', 'Interesting', 'Tech', 'Tutorial'])],
            'poster' => 'required|image|max:10240|file',
            'video' => 'required|mimetypes:video/mp4,video/avi,video/x-matroska|max:307200|file',
            'duration' => 'required|numeric',
            'type' => ['required', 'string', Rule::in(['video/mp4', 'video/avi', 'video/x-matroska'])]
        ];
    }
}
