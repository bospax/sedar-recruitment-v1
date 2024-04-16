<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintManpowerDefault extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'position_id' => $this->position_id,
            'salary_structure' => $this->salary_structure,
            'jobrate_name' => $this->jobrate_name,
            'job_level' => $this->job_level,
            'expected_salary' => $this->expected_salary,
            'manpower_count' => $this->manpower_count,
            'employment_type' => $this->employment_type,
            'employment_type_label' => $this->employment_type_label,
            'requisition_type' => $this->requisition_type,
            'requisition_type_mark' => $this->requisition_type_mark,
            'attachment' => $this->attachment,
            'justification' => $this->justification,
            'replacement_for' => $this->replacement_for,
            'manpower_form_type' => $this->manpower_form_type,
            'level' => $this->level,
            'current_status' => $this->current_status,
            'current_status_mark' => $this->current_status_mark,
            'requestor_id' => $this->requestor_id,
            'requestor_remarks' => $this->requestor_remarks,
            'is_fulfilled' => $this->is_fulfilled,
            'date_fulfilled' => $this->date_fulfilled,
            'tobe_hired' => $this->tobe_hired,
            'created_at' => $created,
            'position_name' => $this->position_name,
            'department_name' => $this->department_name,

            'superior_first_name' => $this->superior_first_name,
            'superior_middle_name' => $this->superior_middle_name,
            'superior_last_name' => $this->superior_last_name,
            'superior_suffix' => $this->superior_suffix,
            'superior_full_name' => $this->superior_first_name.' '.$this->superior_middle_name.' '.$this->superior_last_name.' '.$this->superior_suffix,

            'superior_position_name' => $this->superior_position_name,

            'requestor_first_name' => $this->requestor_first_name,
            'requestor_middle_name' => $this->requestor_middle_name,
            'requestor_last_name' => $this->requestor_last_name,
            'requestor_suffix' => $this->requestor_suffix,
            'requestor_full_name' => $this->requestor_first_name.' '.$this->requestor_middle_name.' '.$this->requestor_last_name.' '.$this->requestor_suffix,

            'employee_tobe_hired_first_name' => $this->employee_tobe_hired_first_name,
            'employee_tobe_hired_middle_name' => $this->employee_tobe_hired_middle_name,
            'employee_tobe_hired_last_name' => $this->employee_tobe_hired_last_name,
            'employee_tobe_hired_suffix' => $this->employee_tobe_hired_suffix,

            'employee_hired_date' => $this->employee_hired_date,
        ];
    }
}
