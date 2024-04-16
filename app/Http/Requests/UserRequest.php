<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'employee_id' => ['required'],
            'name' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u'],
            'username' => ['required', 'unique:users'],
            'password' => ['required', 'min:8', 'confirmed'],
            'role_id' => ['required']
        ];
    }

    function messages()
    {
        return [
            'name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.',
            'password.confirmed' => 'confirmation_failed',
            'role_id.required' => 'The role field is required'
        ];
    }
}
