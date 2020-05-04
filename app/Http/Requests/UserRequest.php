<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'fullname' => 'required|string',
            'username' => 'required|unique:users|min:4|string',
            'email' => 'required|email|unique:users|string',
            'password' => 'required|min:8|string|confirmed',
            'ip_list' => 'required|ip'
        ];
    }
}
