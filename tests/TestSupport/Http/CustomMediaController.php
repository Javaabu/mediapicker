<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Http;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Javaabu\Mediapicker\Http\Controllers\MediaController;

class CustomMediaController extends MediaController
{
    public function index(Request $request): View|Responsable|JsonResponse
    {
        return response()->json(['custom' => true]);
    }
}
