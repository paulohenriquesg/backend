<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class UploadRequest extends Request
{
    public function commonRules(): array
    {
        return [];
    }
}
