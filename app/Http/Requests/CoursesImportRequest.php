<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoursesImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_course' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_course.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
