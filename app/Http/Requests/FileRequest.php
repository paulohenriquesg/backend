<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class FileRequest extends Request
{
    public function commonRules(): array
    {
        return [
            'name' => 'required|string|unique:files,name',
            'create_datetime' => 'required|date|date_format:Y-m-d H:i:s',
            'checksum' => 'required|string',
        ];
    }

    public function storeRules(): array
    {
        return [
            'chunks_count' => 'required|integer|min:1',
        ];
    }
}
