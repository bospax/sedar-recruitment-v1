<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeFile extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'tid' => $this->id,
            'temployee_id' => $this->employee_id,
            'tfile_type' => $this->file_type,
            'tcabinet_number' => $this->cabinet_number,
            'tdescription' => $this->description,
            'tfile' => $this->file
        ];
    }
}
