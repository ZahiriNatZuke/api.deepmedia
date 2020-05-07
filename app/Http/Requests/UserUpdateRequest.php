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
            'fullname' => 'sometimes|string',
            'username' => 'sometimes|unique:users|min:4|string',
            'email' => 'sometimes|unique:users|email:rfc,strict,spoof,filter|string',
            'new_password' => 'sometimes|confirmed|min:8',
            'avatar_src' => 'sometimes|file|image|max:10240'
        ];
    }
}