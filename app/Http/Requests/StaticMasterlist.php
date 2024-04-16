<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaticMasterlist extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'name' => ['required'],
        ];
    }

    function messages()
    {
        return [
            // 'name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
