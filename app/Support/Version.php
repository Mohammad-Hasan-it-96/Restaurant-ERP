<?php

namespace App\Support;

/**
 * Version
 *
 * The single resolver for the application's semantic version + release notes.
 * Everything that needs the version — the public /api/v1/version endpoint, the
 * admin dashboard/sidebar, the health report, the Release-notes page, and the
 * app_version() helper — funnels through here.
 *
 * v1 reads straight from config/version.php (no DB hit). This class is the ONLY
 * place that resolves the version, so a future build-SHA / channel layer can be
 * added here without touching any call site.
 *
 * Usage:
 *   Version::current()        // "1.0.0"
 *   app_version()             // global helper wrapper
 */
class Version
{
    /**
     * The current semantic version, e.g. "1.0.0".
     */
    public static function current(): string
    {
        // ── Future seam ──────────────────────────────────────────────
        // A build SHA / release channel could be appended here later; v1 is
        // config-only.
        return (string) config('version.current', '0.0.0');
    }

    /**
     * The release date of the current version (Y-m-d), if recorded.
     */
    public static function releasedAt(): ?string
    {
        $date = config('version.released_at');

        return $date !== null && $date !== '' ? (string) $date : null;
    }

    /**
     * All releases, newest first. Each entry: ['version', 'date', 'notes' => []].
     *
     * @return array<int, array{version:string,date:string,notes:array<int,string>}>
     */
    public static function releases(): array
    {
        $releases = config('version.releases', []);

        return is_array($releases) ? array_values($releases) : [];
    }

    /**
     * The most recent release entry (first of releases()), or null.
     *
     * @return array{version:string,date:string,notes:array<int,string>}|null
     */
    public static function latest(): ?array
    {
        return static::releases()[0] ?? null;
    }
}
