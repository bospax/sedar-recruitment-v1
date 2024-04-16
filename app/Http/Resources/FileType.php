<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FileType extends JsonResource
{
    public function toArray($request)
    {
        // modifying the returned resource
        
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'filetype_name' => $this->filetype_name,
            'created_at' => $created
        ];
    }
}
