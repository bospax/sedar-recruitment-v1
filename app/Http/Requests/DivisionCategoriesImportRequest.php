<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DivisionCategoriesImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_division_category' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_division_category.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
