<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintManpowerEmployeeMovement extends JsonResource
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
        // $inclusive_date_start = Carbon::createFromFormat('Y-m-d H:i:s', $this->inclusive_date_start)->format('M d, Y');
        // $inclusive_date_end = Carbon::createFromFormat('Y-m-d H:i:s', $this->inclusive_date_end)->format('M d, Y');
        $inclusive_date_start = Carbon::parse($this->inclusive_date_start)->format('M d, Y');
        $inclusive_date_end = Carbon::parse($this->inclusive_date_end)->format('M d, Y');

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

            'employee_id' => $this->employee_id,
            'change_position_id' => $this->change_position_id,
            'change_reason' => $this->change_reason,
            'for_da' => $this->for_da,
            'manpower_id' => $this->manpower_id,
            'additional_rate' => $this->additional_rate,
            'jobrate_name' => $this->jobrate_name,
            'salary_structure' => $this->salary_structure,
            'job_level' => $this->job_level,
            'allowance' => $this->allowance,
            'job_rate' => $this->job_rate,
            'salary' => $this->salary,

            'datachange_id' => $this->datachange_id,
            'inclusive_date_start' => $inclusive_date_start,
            'inclusive_date_end' => $inclusive_date_end,

            'datachange_employees_prefix_id' => $this->datachange_employees_prefix_id,
            'datachange_employees_id_number' => $this->datachange_employees_id_number,
            'datachange_employees_first_name' => $this->datachange_employees_first_name,
            'datachange_employees_middle_name' => $this->datachange_employees_middle_name,
            'datachange_employees_last_name' => $this->datachange_employees_last_name,
            'datachange_employees_suffix' => $this->datachange_employees_suffix,

            'datachange_positions_position_name' => $this->datachange_positions_position_name,
            'datachange_positions_department_name' => $this->datachange_positions_department_name,
            'datachange_positions_subunit_name' => $this->datachange_positions_subunit_name,
            'datachange_positions_jobband_name' => $this->datachange_positions_jobband_name,

            'change_position_name' => $this->change_position_name,
            'change_department_name' => $this->change_department_name,
            'change_subunit_name' => $this->change_subunit_name,
            'change_jobband_name' => $this->change_jobband_name,
            'prev_additional_rate' => $this->prev_additional_rate,
            'prev_jobrate_name' => $this->prev_jobrate_name,
            'prev_salary_structure' => $this->prev_salary_structure,
            'prev_job_level' => $this->prev_job_level,
            'prev_allowance' => $this->prev_allowance,
            'prev_job_rate' => $this->prev_job_rate,
            'prev_salary' => $this->prev_salary,

            'employee_tobe_hired_first_name' => $this->employee_tobe_hired_first_name,
            'employee_tobe_hired_middle_name' => $this->employee_tobe_hired_middle_name,
            'employee_tobe_hired_last_name' => $this->employee_tobe_hired_last_name,
            'employee_tobe_hired_suffix' => $this->employee_tobe_hired_suffix,

            'employee_hired_date' => $this->employee_hired_date,

            'measures' => json_decode($this->measures),
            'assessment_mark' => $this->assessment_mark,
            'prev_measures' => json_decode($this->prev_measures),
        ];
    }
}
