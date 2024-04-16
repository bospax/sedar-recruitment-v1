<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DegreeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'degree_name' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u']
        ];
    }

    function messages()
    {
        return [
            'degree_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
