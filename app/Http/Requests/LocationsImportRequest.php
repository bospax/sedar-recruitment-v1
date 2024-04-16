<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationsImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_location' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_location.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
