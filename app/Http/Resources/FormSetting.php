<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FormSetting extends JsonResource
{
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'form_type' => $this->form_type,
            'subunit_id' => $this->subunit_id,
            'number_of_levels' => $this->number_of_levels,
            'label' => $this->label,
            'subunit_name' => $this->subunit_name,
            'created_at' => $created
        ];
    }
}
