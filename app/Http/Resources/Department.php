<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class Department extends JsonResource
{
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'department_code' => $this->department_code,
            'department_name' => $this->department_name,
            'division_id' => $this->division_id,
            'division_cat_id' => $this->division_cat_id,
            'company_id' => $this->company_id,
            'location_id' => $this->location_id,
            'division_name' => $this->division_name,
            'category_name' => $this->category_name,
            'company_name' => $this->company_name,
            'location_name' => $this->location_name,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'created_at' => $created
        ];
    }
}
