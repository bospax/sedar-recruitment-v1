<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobBandRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'jobband_name' => ['required', "regex:/^[0-9\pL\s\()&\/.,'_-]+$/u", 'unique:jobbands,jobband_name']
        ];
    }

    function messages()
    {
        return [
            'jobband_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
