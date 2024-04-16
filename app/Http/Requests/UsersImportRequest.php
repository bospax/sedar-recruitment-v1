<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsersImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_user' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_user.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
