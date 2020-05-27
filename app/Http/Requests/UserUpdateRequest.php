<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
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
            'fullname' => 'nullable|string',
            'username' => 'nullable|min:4|string',
            'email' => 'nullable|email:rfc,strict,spoof,filter',
            'avatar' => 'nullable|file|image|max:10240'
        ];
    }
}
