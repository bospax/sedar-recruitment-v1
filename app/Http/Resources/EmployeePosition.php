<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeePosition extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'position_id' => $this->position_id,
            'jobrate_id' => $this->jobrate_id,
            'additional_rate' => $this->additional_rate,
            'additional_tool' => $this->additional_tool,
            'schedule' => $this->schedule,
            'emp_shift' => $this->emp_shift,
            'remarks' => $this->remarks,
            'prefix_id' => $this->prefix_id,
            'id_number' => $this->id_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'gender' => $this->gender,
            'image' => $this->image,
            'position_name' => $this->position_name,
            'salary_structure' => $this->salary_structure,
            'job_level' => $this->job_level,
            'jobrate_name' => $this->jobrate_name,
            'department_name' => $this->department_name,
            'subunit_name' => $this->subunit_name,
            'full_id_number' => $this->prefix_id.'-'.$this->id_number, 
            'full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'superior_name' => $this->superior_name,
        ];
    }
}
