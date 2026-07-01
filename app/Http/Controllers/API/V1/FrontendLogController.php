<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FrontendLogController extends Controller
{
    use ApiResponse;

    /** Max serialized size of the client-supplied `data` blob (bytes). */
    private const MAX_DATA_BYTES = 4096;

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
            // Cap the number of top-level entries; the serialized-size guard below
            // bounds nested payloads so a single log line can't be inflated.
            'data' => ['nullable', 'array', 'max:50'],
        ]);

        $data = $request->input('data', []);
        if (strlen((string) json_encode($data)) > self::MAX_DATA_BYTES) {
            $data = ['_truncated' => true];
        }

        // ip / user_agent / request_id are auto-attached via InjectLogContext,
        // so a browser-side error correlates to its backend request.
        logService()->warning('frontend.error', [
            'type' => $request->input('type'),
            'message' => $request->input('message'),
            'data' => $data,
        ]);

        return $this->success(null, 'logged');
    }
}
