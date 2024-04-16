<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FormArchiveManpowerComplete extends JsonResource
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
        $updated = Carbon::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('M d, Y');

        $date_fullfilled = ($this->date_fulfilled) ? Carbon::createFromFormat('Y-m-d', $this->date_fulfilled)->format('M d, Y') : '';
        $review_date = Carbon::createFromFormat('M d, Y h:i a', $this->review_date)->format('M d, Y');
        $effectivity_date = ($this->effectivity_date) ? Carbon::createFromFormat('Y-m-d', $this->effectivity_date)->format('M d, Y') : '';
        $hired_date =  ($this->hired_date_fix) ? Carbon::createFromFormat('Y-m-d', $this->hired_date_fix)->format('M d, Y') : '';
        $startDate = Carbon::parse($review_date);
        $endDate = ($this->hiring_type == 'EXTERNAL HIRE') ? Carbon::parse($hired_date) : Carbon::parse($effectivity_date);
        $duration = 0;

        if ($this->hired_date && $this->hiring_type == 'EXTERNAL HIRE') {
            $duration = $endDate->diffInDays($startDate);
        }

        if ($this->effectivity_date && $this->hiring_type == 'INTERNAL HIRE') {
            $duration = $endDate->diffInDays($startDate);
        }

        $requestor_name = $this->first_name.' '.$this->middle_name.' '.$this->last_name.' '.$this->suffix;
        $tobe_hired = $this->tobe_hired_first_name.' '.$this->tobe_hired_middle_name.' '.$this->tobe_hired_last_name.' '.$this->tobe_hired_suffix;

        $data = [];

        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'form_type' => $this->form_type,
            'requestor_id' => $this->requestor_id,
            'requestor_remarks' => $this->requestor_remarks,
            'is_fulfilled' => $this->is_fulfilled,

            'date_fulfilled' => $date_fullfilled,
            'hiring_type' => $this->hiring_type,
            'tobe_hired' => $tobe_hired,
            'hired_date' => $this->hired_date,
            'review_date' => $review_date,
            'effectivity_date' => $effectivity_date,
            'duration' => $duration,
            'employee_data_status' => $this->employee_data_status,

            'subunit_name' => $this->subunit_name,
            'job_level' => $this->job_level,
            'expected_salary' => $this->expected_salary,
            'employment_type' => $this->employment_type,

            'created_at' => $created,
            'updated_at' => $updated,
            'prefix_id' => $this->prefix_id,
            'id_number' => $this->id_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'requestor_position' => $this->requestor_position,
            'requestor_department' => $this->requestor_department,
            'req_full_id_number' => $this->prefix_id.'-'.$this->id_number, 
            'req_full_name' => $this->last_name.', '.$this->first_name.' '.$this->middle_name,
            'req_full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'requisition_type_mark' => $this->requisition_type_mark,
            'requested_position_name' => $this->requested_position_name,
        ];

        return $data;
    }
}
