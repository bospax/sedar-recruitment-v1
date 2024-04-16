<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormApproverRequest extends FormRequest
{
    public function rules()
    {
        return [
            'employee_id' => ['required'],
            'form_setting_id' => ['required'],
            'action' => ['required'],
            'approved_mark' => ['required'],
            'rejected_mark' => ['required'],
        ];
    }
}
