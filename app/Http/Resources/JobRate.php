<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class JobRate extends JsonResource
{
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');
        $desc = ($this->jobrate_name) ? '('.$this->jobrate_name.')' : '';
        $detailed_structure = ($this->allowance) ? $this->job_level.' - '.$this->salary_structure.' '.$desc.' - '.$this->job_rate.' | ALLOWANCE: '.$this->allowance : $this->job_level.' - '.$this->salary_structure.' '.$desc.' - '.$this->job_rate;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'position_id' => $this->position_id,
            'position_title' => $this->position_title,
            'position_name' => $this->position_name,
            'job_level' => $this->job_level,
            'job_rate' => $this->job_rate,
            'allowance' => $this->allowance,
            'salary_structure' => $this->salary_structure,
            'jobrate_name' => $this->jobrate_name,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'detailed_structure' => $detailed_structure,
            'created_at' => $created
        ];
    }
}
