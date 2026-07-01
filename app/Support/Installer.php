<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Installer — single source of truth for install state.
 *
 * "Installed" is a lock file at storage/installed. To avoid forcing the wizard
 * onto installations that predate it, isInstalled() self-heals: an app that is
 * clearly configured (APP_KEY set + DB reachable + users table present) is
 * marked installed automatically.
 */
class Installer
{
    public static function lockPath(): string
    {
        return storage_path('installed');
    }

    public static function isInstalled(): bool
    {
        if (is_file(self::lockPath())) {
            return true;
        }

        if (self::looksConfigured()) {
            self::lock();

            return true;
        }

        return false;
    }

    public static function lock(): void
    {
        @file_put_contents(self::lockPath(), 'installed_at='.date('c').PHP_EOL);
    }

    public static function unlock(): void
    {
        @unlink(self::lockPath());
    }

    /**
     * Heuristic for an app installed before the wizard existed: a real APP_KEY
     * plus a reachable database with the users table.
     */
    private static function looksConfigured(): bool
    {
        if (empty(config('app.key'))) {
            return false;
        }

        try {
            return Schema::hasTable('users');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
