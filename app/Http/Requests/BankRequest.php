<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'bank_name' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u']
        ];
    }

    function messages()
    {
        return [
            'bank_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
