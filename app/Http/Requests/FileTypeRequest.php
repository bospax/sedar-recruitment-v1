<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileTypeRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'filetype_name' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u']
        ];
    }

    function messages()
    {
        return [
            'filetype_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
