<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FrontendLogController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/logs/frontend
     *
     * Accepts: { type, message, data }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:1000'],
            'data' => ['nullable', 'array'],
        ]);

        Log::warning('frontend.error', [
            'type' => $request->input('type'),
            'message' => $request->input('message'),
            'data' => $request->input('data', []),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success(null, 'logged');
    }
}
