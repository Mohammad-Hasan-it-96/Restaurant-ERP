<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application version
    |--------------------------------------------------------------------------
    |
    | The single, committed source of truth for the app's semantic version
    | (MAJOR.MINOR.PATCH). Read everywhere through App\Support\Version — never
    | reference config('version.*') directly. Literal (no env()) so the version
    | always matches the deployed code; bump it in a PR alongside CHANGELOG.md.
    |
    | After changing this file in production, run `php artisan config:cache`.
    |
    */

    'current' => '1.0.0',

    'released_at' => '2026-06-28',

    /*
    | Release / upgrade notes — newest first. Powers the admin "Release notes"
    | page (App\Http\Controllers\Admin\ReleaseNotesController) and mirrors
    | CHANGELOG.md. Each entry: ['version' => string, 'date' => 'Y-m-d',
    | 'notes' => string[]].
    */
    'releases' => [
        [
            'version' => '1.0.0',
            'date' => '2026-06-28',
            'notes' => [
                'Initial release.',
                'Installer wizard for fresh-domain setup (no manual .env editing).',
                'Operational systems: Telegram alerts, activity log, automatic backups, health checks.',
                'Reusable configuration: currency and brand theme, generic seed defaults.',
            ],
        ],
    ],

];
