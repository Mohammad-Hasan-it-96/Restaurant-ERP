<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Support\Version;
use Illuminate\Http\JsonResponse;

/**
 * Public current-version endpoint.
 *
 * GET /api/v1/version → { version, released_at }
 *
 * Low-sensitivity: only the version + date are exposed (the full release notes
 * stay on the admin-gated Release-notes page).
 */
class VersionController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        return $this->success([
            'version' => Version::current(),
            'released_at' => Version::releasedAt(),
        ]);
    }
}
