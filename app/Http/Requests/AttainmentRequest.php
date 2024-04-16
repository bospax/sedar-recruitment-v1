<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttainmentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'attainment_name' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u']
        ];
    }

    function messages()
    {
        return [
            'attainment_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
