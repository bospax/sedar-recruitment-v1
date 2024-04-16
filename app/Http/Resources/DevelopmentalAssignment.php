<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DevelopmentalAssignment extends JsonResource
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
        $date_start = Carbon::createFromFormat('Y-m-d', $this->inclusive_date_start)->format('F d, Y');
        $date_end = Carbon::createFromFormat('Y-m-d', $this->inclusive_date_end)->format('F d, Y');

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'datachange_id' => $this->datachange_id,
            'inclusive_date_start' => $date_start,
            'inclusive_date_end' => $date_end,
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
            'change_position_name' => $this->change_position_name,
            'change_department_name' => $this->change_department_name,
            'measures' => json_decode($this->measures),
            'full_id_number_full_name' => $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name
        ];
    }
}
