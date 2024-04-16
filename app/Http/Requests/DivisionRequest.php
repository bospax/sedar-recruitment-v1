<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DivisionRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'division_name' => ['required', "regex:/^[0-9\pL\s\()&\/.,'_-]+$/u"]
        ];
    }

    function messages()
    {
        return [
            'division_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
