<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Orion\Http\Resources\Resource;

class UploadResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = parent::toArray($request);

        $response['status'] = $this->resource->status->name;

        return $response;
    }
}
