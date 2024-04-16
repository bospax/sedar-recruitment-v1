<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ManpowerWithExpectedSalary extends JsonResource
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
        $desc = ($this->jobrate_name) ? '('.$this->jobrate_name.')' : '';

        return [
            'id' => $this->id,
            'code' => $this->code,
            'position_id' => $this->position_id,
            'jobrate_id' => $this->jobrate_id,
            'manpower_count' => $this->manpower_count,
            'employment_type' => $this->employment_type,
            'employment_type_label' => $this->employment_type_label,
            'requisition_type' => $this->requisition_type,
            'requisition_type_mark' => $this->requisition_type_mark,
            'attachment' => explode(',', $this->attachment),
            'justification' => $this->justification,
            'replacement_for' => $this->replacement_for,
            'form_type' => $this->form_type,
            'level' => $this->level,
            'current_status' => $this->current_status,
            'current_status_mark' => $this->current_status_mark,
            'requestor_id' => $this->requestor_id,
            'requestor_remarks' => $this->requestor_remarks,
            'is_fulfilled' => $this->is_fulfilled,
            'tobe_hired' => $this->tobe_hired,
            'date_fulfilled' => $this->date_fulfilled,
            'position_name' => $this->position_name,
            'payrate' => $this->payrate,
            'shift' => $this->shift,
            'schedule' => $this->schedule,
            'team' => $this->team,
            'jobrate_name' => $this->jobrate_name,
            'jobband_id' => $this->jobband_id,
            'jobband_name' => $this->jobband_name,
            'job_level' => $this->job_level,
            'salary_structure' => $this->salary_structure,
            's_full_name' => ($this->s_prefix_id) ? $this->s_last_name.', '.$this->s_first_name.' '.$this->s_suffix.' '.$this->s_middle_name : '',
            's_full_id_number_full_name' => ($this->s_prefix_id) ? $this->s_prefix_id.'-'.$this->s_id_number.' '.$this->s_last_name.', '.$this->s_first_name.' '.$this->s_suffix.' '.$this->s_middle_name : '',
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'tobehired_firstname' => $this->tobehired_firstname,
            'tobehired_lastname' => $this->tobehired_lastname,
            'requestor_position' => $this->requestor_position,
            'requestor_department' => $this->requestor_department,
            'requestor_subunit' => $this->requestor_subunit,
            'replacement_id' => $this->replacement_id,
            'replacement_first_name' => $this->replacement_first_name,
            'replacement_last_name' => $this->replacement_last_name,
            'position_department' => $this->position_department,
            'detailed_structure' => $this->job_level.' - '.$this->salary_structure.' '.$desc,
            'replacement_full_id_number_full_name' => $this->replacement_prefix_id.'-'.$this->replacement_id_number.' '.$this->replacement_last_name.', '.$this->replacement_first_name.' '.$this->replacement_suffix.' '.$this->replacement_middle_name,
            'position_name_tobehired' => $this->position_name_tobehired,
            'department_name_tobehired' => $this->department_name_tobehired,
            'subunit_name_tobehired' => $this->subunit_name_tobehired,
            'created_at' => $created,
            'expected_salary' => $this->expected_salary,
            'salary_structure' => $this->salary_structure,
            'jobrate_name' => $this->jobrate_name,
            'job_level' => $this->job_level,
            'full_salary_structure' => $this->job_level.' | '.$this->salary_structure.' | '.$this->jobrate_name,
            'status_mark' => $this->status_mark,
            'review_date' => $this->review_date,
            'daevaluation_code' => $this->daevaluation_code,
            'for_da' => $this->for_da
        ];
    }
}
