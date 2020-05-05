<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'state' => 'required|string',
            'category' => 'required|string',
            'poster' => 'required|image|max:10240',
            'video' => 'required|mimes:mkv,mp4,avi|max:307200'
        ];
    }
}
