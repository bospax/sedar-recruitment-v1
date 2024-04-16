<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDataChangeDetail extends JsonResource
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
            'code' => $this->code,
            'employee_id' => $this->employee_id,
            'attachment' => $this->attachment,
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
            'gender' => $this->gender,
            'image' => $this->image,
            'full_id_number' => $this->prefix_id.'-'.$this->id_number, 
            'full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
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
            'status_mark' => $this->status_mark,
            'review_date' => $this->review_date,

            'change_reason' => $this->change_reason,

            'department_name' => $this->department_name,
            'position_name' => $this->position_name,
            'jobband_name' => $this->jobband_name,
            'subunit_name' => $this->subunit_name,
            'location_name' => $this->location_name,
            'company_name' => $this->company_name,
            'division_name' => $this->division_name,
            'category_name' => $this->category_name,

            'position_id' => $this->position_id,
            'department_id' => $this->department_id,
            'subunit_id' => $this->subunit_id,
            'location_id' => $this->location_id,
            'company_id' => $this->company_id,
            'division_id' => $this->division_id,
            'category_id' => $this->category_id,

            'new_position_name' => $this->new_position_name,
            'new_department_name' => $this->new_department_name,
            'new_subunit_name' => $this->new_subunit_name,
            'new_location_name' => $this->new_location_name,
            'new_company_name' => $this->new_company_name,
            'new_division_name' => $this->new_division_name,
            'new_category_name' => $this->new_category_name,

            'new_position_id' => $this->new_position_id,
            'new_department_id' => $this->new_department_id,
            'new_subunit_id' => $this->new_subunit_id,
            'new_location_id' => $this->new_location_id,
            'new_company_id' => $this->new_company_id,
            'new_division_id' => $this->new_division_id,
            'new_division_cat_id' => $this->new_division_cat_id,

            'current_job_level' => $this->current_job_level,
            'current_salary_structure' => $this->current_salary_structure,
            'current_jobrate_name' => $this->current_jobrate_name,
            'current_allowance' => $this->current_allowance,
            'current_job_rate' => $this->current_job_rate,
            'current_salary' => $this->current_salary,
            'current_additional_rate' => $this->current_additional_rate,

            'new_job_level' => $this->new_job_level,
            'new_salary_structure' => $this->new_salary_structure,
            'new_jobrate_name' => $this->new_jobrate_name,
            'new_allowance' => $this->new_allowance,
            'new_job_rate' => $this->new_job_rate,
            'new_salary' => $this->new_salary,
            'new_additional_rate' => $this->new_additional_rate,
        ];
    }
}
