<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PositionsImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_position' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_position.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
