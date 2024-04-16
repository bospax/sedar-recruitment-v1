<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TitlesImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_title' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_title.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
