<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Browse the business audit trail with search + filters (admin only).
     * Mirrors the Orders index pattern: allowlisted sort + withQueryString.
     */
    public function index(Request $request)
    {
        $allowed = ['created_at', 'action'];
        $sortBy = in_array($request->input('sort'), $allowed) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $logs = ActivityLog::query()
            ->action($request->input('action'))
            ->between($request->input('from'), $request->input('to'))
            ->search($request->input('search'))
            ->orderBy($sortBy, $direction)
            ->paginate(20)
            ->withQueryString();

        // Distinct actions for the filter dropdown.
        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.activity-logs.index', compact('logs', 'actions'));
    }
}
