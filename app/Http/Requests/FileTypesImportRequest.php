<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileTypesImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_filetype' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_filetype.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
