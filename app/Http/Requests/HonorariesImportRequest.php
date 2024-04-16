<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HonorariesImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_honorary' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_honorary.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
