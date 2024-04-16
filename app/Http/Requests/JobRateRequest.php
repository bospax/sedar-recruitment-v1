<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobRateRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'position_title' => ['required'],
            'job_level' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u'],
            'job_rate' => ['required', 'regex:/^[0-9\.,]+$/u'],
            'jobrate_name' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u'],
            'salary_structure' => ['required', Rule::notIn(['null'])]
        ];
    }

    function messages()
    {
        return [
            'position_title.required' => 'The position field is required',
            'jobrate_name.required' => 'The job rate description is required',
            'jobrate_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.',
            'salary_structure.not_in' => 'The salary structure must have a valid value',
        ];
    }
}
