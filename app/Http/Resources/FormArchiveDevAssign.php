<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FormArchiveDevAssign extends JsonResource
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
        $updated = Carbon::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('M d, Y h:i a');
        $effectivity_date = ($this->effectivity_date) ? Carbon::createFromFormat('Y-m-d', $this->effectivity_date)->format('M d, Y') : '';

        $data = [];

        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'form_type' => $this->form_type,
            'requestor_id' => $this->requestor_id,
            'requestor_remarks' => $this->requestor_remarks,
            'is_fulfilled' => $this->is_fulfilled,
            'date_fulfilled' => $this->date_fulfilled,
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
            'req_full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'req_full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'inc_full_name' => $this->incumbent_last_name.', '.$this->incumbent_first_name.' '.$this->incumbent_suffix.' '.$this->incumbent_middle_name,
            'designation_position_name' => $this->designation_position_name,
            'employee_data_status' => $this->employee_data_status,
            'effectivity_date' => $effectivity_date
        ];

        return $data;
    }
}
