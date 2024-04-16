<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubunitsImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_subunit' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_subunit.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
