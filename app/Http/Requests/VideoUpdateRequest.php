<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoUpdateRequest extends FormRequest
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
            'title' => 'nullable|string',
            'description' => 'nullable|max:255|string',
            'state' => 'nullable|string',
            'category' => 'nullable|string',
            'poster' => 'nullable|image|max:10240|file',
            'video' => 'nullable|mimes:mkv,mp4,avi|max:307200|file',
            'duration' => 'nullable'
        ];
    }
}
