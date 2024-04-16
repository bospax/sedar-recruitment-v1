<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'prefix_id' => ['required'],
            'id_number' => ['required', 'numeric', 'unique:employees,id_number'],
            'first_name' => ['required', 'regex:/^[0-9\pL\s\()&\/,_-]+$/u'],
            'middle_name' => ['nullable', 'regex:/^[0-9\pL\s\()&\/,_-]+$/u'],
            'last_name' => ['required', 'regex:/^[0-9\pL\s\()&\/,_-]+$/u'],
            'suffix' => ['nullable','regex:/^[0-9\pL\s\()&\/,_-]+$/u'],
            'birthdate' => ['required'],
            'religion' => ['required'],
            'civil_status' => ['required'],
            'gender' => ['required']
        ];
    }

    function messages()
    {
        return [
            'first_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.'
        ];
    }
}
