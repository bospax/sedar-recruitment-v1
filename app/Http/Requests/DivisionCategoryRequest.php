<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DivisionCategoryRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'category_name' => ['required', "regex:/^[0-9\pL\s\()&\/.,'_-]+$/u"]
        ];
    }

    function messages()
    {
        return [
            'category_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
