<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class FileRequest extends Request
{
    public function storeRules(): array
    {
        return [
            'name' => 'required|string|unique:files,name',
            'create_datetime' => 'required|date|date_format:Y-m-d H:i:s',
            'checksum' => 'string',
            'chunks_count' => 'required|integer|min:1',
        ];
    }

    public function updateRules(): array
    {
        return [
            'name' => 'string|unique:files,name',
            'create_datetime' => 'date|date_format:Y-m-d H:i:s',
            'checksum' => 'string',
        ];
    }
}
