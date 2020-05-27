<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'state' => ['nullable', 'string', Rule::in(['Public', 'Private'])],
            'category' => ['nullable', 'string', Rule::in(['Gameplay', 'Musical', 'Joke', 'Interesting', 'Tech', 'Tutorial'])],
            'poster' => 'nullable|image|max:10240|file',
            'video' => 'nullable|mimetypes:video/mp4,video/avi,video/x-matroska|max:307200|file',
            'duration' => 'nullable|numeric',
            'type' => ['nullable', Rule::in(['video/mp4', 'video/avi', 'video/x-matroska'])]
        ];
    }
}
