<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ManpowerDetail extends JsonResource
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
            'form_type' => $this->form_type,
            'label' => $this->label,
            'action' => $this->action,
            'id' => $this->id,
            'code' => $this->code,
            'position_id' => $this->position_id,
            'jobrate_id' => $this->jobrate_id,
            'manpower_count' => $this->manpower_count,
            'employment_type' => $this->employment_type,
            'employment_type_label' => $this->employment_type_label,
            'requisition_type' => $this->requisition_type,
            'requisition_type_mark' => $this->requisition_type_mark,
            'attachment' => $this->attachment,
            'justification' => $this->justification,
            'replacement_for' => $this->replacement_for,
            'form_type' => $this->form_type,
            'level' => $this->level,
            'current_status' => $this->current_status,
            'current_status_mark' => $this->current_status_mark,
            'requestor_id' => $this->requestor_id,
            'requestor_remarks' => $this->requestor_remarks,
            'is_fulfilled' => $this->is_fulfilled,
            'date_fulfilled' => $this->date_fulfilled,
            'created_at' => $created,
            'req_prefix_id' => $this->req_prefix_id,
            'req_id_number' => $this->req_id_number,
            'req_first_name' => $this->req_first_name,
            'req_middle_name' => $this->req_middle_name,
            'req_last_name' => $this->req_last_name,
            'req_suffix' => $this->req_suffix,
            'position_id' => $this->position_id,
            'position_name' => $this->position_name,
            // 'requestor_position' => $this->requestor_position,
            'department_name' => $this->department_name,
            'subunit_name' => $this->subunit_name,
            'requested_position_name' => $this->requested_position_name
        ];
    }
}
