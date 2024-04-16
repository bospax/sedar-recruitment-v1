<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'location_name' => ['required', "regex:/^[0-9\pL\s\()&\/.,'_-]+$/u"],
        ];
    }

    function messages()
    {
        return [
            'location_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
