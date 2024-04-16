<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DegreesImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_degree' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_degree.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
