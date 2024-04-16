<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DataChange extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'change_position_id' => $this->change_position_id,
            'change_reason' => $this->change_reason,
            'for_da' => $this->for_da,
            'manpower_id' => $this->manpower_id,
            'form_type' => $this->form_type,
            'level' => $this->level,
            'current_status' => $this->current_status,
            'current_status_mark' => $this->current_status_mark,
            'requestor_id' => $this->requestor_id,
            'requestor_remarks' => $this->requestor_remarks,
            'is_fulfilled' => $this->is_fulfilled,
            'date_fulfilled' => $this->date_fulfilled,
            'created_at' => $created,
            'prefix_id' => $this->prefix_id,
            'id_number' => $this->id_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'position_name' => $this->position_name,
            'department_name' => $this->department_name,
            'jobband_name' => $this->jobband_name,
            'change_position_name' => $this->change_position_name,
            'change_department_name' => $this->change_department_name,
            'change_jobband_name' => $this->change_jobband_name,
            'full_id_number_full_name' => $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'additional_rate' => $this->additional_rate,
            'jobrate_name' => $this->jobrate_name,
            'salary_structure' => $this->salary_structure,
            'job_level' => $this->job_level,
            'allowance' => $this->allowance,
            'job_rate' => $this->job_rate,
            'salary' => $this->salary,
            'prev_additional_rate' => $this->prev_additional_rate,
            'prev_jobrate_name' => $this->prev_jobrate_name,
            'prev_salary_structure' => $this->prev_salary_structure,
            'prev_job_level' => $this->prev_job_level,
            'prev_allowance' => $this->prev_allowance,
            'prev_job_rate' => $this->prev_job_rate,
            'prev_salary' => $this->prev_salary,
        ];
    }
}
