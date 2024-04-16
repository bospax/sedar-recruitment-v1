<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeAttainmentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'employee_id' => ['required'],
            'attainment' => ['required'],
            // 'institution' => ['required'],
            'course' => ['required'],
            'degree' => ['required'],
            'gpa' => ['nullable','regex:/^(?=.+)(?:[1-9]\d*|0)?(?:\.\d+)?$/'],
        ];
    }
}
