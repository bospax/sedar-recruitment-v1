<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class Subunit extends JsonResource
{
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'department_id' => $this->department_id,
            'subunit_name' => $this->subunit_name,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'created_at' => $created,
            'department_name' => $this->department_name
        ];
    }
}
