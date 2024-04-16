<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'company_name' => ['required', "regex:/^[0-9\pL\s\()&\/.,'_-]+$/u"]
        ];
    }

    function messages()
    {
        return [
            'company_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
