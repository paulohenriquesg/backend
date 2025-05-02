<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Upload\Settings;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function get(): JsonResponse
    {
        return response()->json([
            'data' => [
                'upload_max_size' => Settings::getPostMaxSize(),
            ],
        ]);
    }
}
