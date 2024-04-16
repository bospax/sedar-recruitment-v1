<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DivisionsImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_division' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_division.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
