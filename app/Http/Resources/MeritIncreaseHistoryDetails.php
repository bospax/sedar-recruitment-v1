<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MeritIncreaseHistoryDetails extends JsonResource
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
        $proposed_desc = ($this->proposed_jobrate_name) ? '('.$this->proposed_jobrate_name.')' : '';

        return [
            'id' => $this->id,
            'code' => $this->code,
            'employee_id' => $this->employee_id,
            'attachment' => $this->attachment,
            'form_type' => $this->form_type,
            'level' => $this->level,
            'current_status' => $this->current_status,
            'current_status_mark' => $this->current_status_mark,
            'status' => $this->status,
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
            'gender' => $this->gender,
            'image' => $this->image,
            'full_id_number' => $this->prefix_id.'-'.$this->id_number, 
            'full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'employment_type' => $this->employment_type,
            'employment_type_label' => $this->employment_type_label,
            'employment_date_start' => $this->employment_date_start,
            'employment_date_end' => $this->employment_date_end,
            'regularization_date' => $this->regularization_date,
            'hired_date' => $this->hired_date,
            'position_id' => $this->position_id,
            'position_name' => $this->position_name,
            'department_name' => $this->department_name,
            'subunit_name' => $this->subunit_name,
            'r_prefix_id' => $this->r_prefix_id,
            'r_id_number' => $this->r_id_number,
            'r_first_name' => $this->r_first_name,
            'r_middle_name' => $this->r_middle_name,
            'r_last_name' => $this->r_last_name,
            'r_suffix' => $this->r_suffix,
            'r_full_id_number_full_name' => ($this->referrer_id) ? $this->r_prefix_id.'-'.$this->r_id_number.' '.$this->r_last_name.', '.$this->r_first_name.' '.$this->r_suffix.' '.$this->r_middle_name : '',
            'req_prefix_id' => $this->req_prefix_id,
            'req_id_number' => $this->req_id_number,
            'req_first_name' => $this->req_first_name,
            'req_middle_name' => $this->req_middle_name,
            'req_last_name' => $this->req_last_name,
            'req_suffix' => $this->req_suffix,
            'req_full_id_number_full_name' => ($this->requestor_id) ? $this->req_prefix_id.'-'.$this->req_id_number.' '.$this->req_last_name.', '.$this->req_first_name.' '.$this->req_suffix.' '.$this->req_middle_name : '',
            'requestor_position' => $this->requestor_position,
            'jobband_name' => $this->jobband_name,
            'jobrate_id' => $this->jobrate_id,
            'job_level' => $this->job_level,
            'job_rate' => $this->job_rate,
            'salary_structure' => $this->salary_structure,
            'jobrate_name' => $this->jobrate_name,
            'detailed_structure' => $this->job_level.' - '.$this->salary_structure.' '.$desc,
            'proposed_jobrate_id' => $this->proposed_jobrate_id,
            'proposed_job_level' => $this->proposed_job_level,
            'proposed_job_rate' => $this->proposed_job_rate,
            'proposed_salary_structure' => $this->proposed_salary_structure,
            'proposed_jobrate_name' => $this->proposed_jobrate_name,
            'proposed_detailed_structure' => $this->proposed_job_level.' - '.$this->proposed_salary_structure.' '.$proposed_desc,
        ];
    }
}
