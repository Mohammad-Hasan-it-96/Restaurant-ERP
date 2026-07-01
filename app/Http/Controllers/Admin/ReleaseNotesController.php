<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Version;

class ReleaseNotesController extends Controller
{
    /**
     * Show the upgrade / release notes (admin only).
     *
     * Reads the committed release list from config/version.php via the Version
     * resolver — no DB hit.
     */
    public function index()
    {
        return view('admin.release-notes.index', [
            'releases' => Version::releases(),
            'current' => Version::current(),
        ]);
    }
}
