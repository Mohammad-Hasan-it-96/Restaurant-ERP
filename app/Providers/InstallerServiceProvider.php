<?php

namespace App\Providers;

use App\Support\EnvWriter;
use App\Support\Installer;
use Illuminate\Support\ServiceProvider;

/**
 * InstallerServiceProvider — puts the app in "pre-DB" mode while not installed.
 *
 * Runs in boot() — after the framework's DB provider (so the install self-heal
 * check is reliable) but before the request reaches StartSession/EncryptCookies,
 * so these config overrides still take effect. A no-op once installed.
 */
class InstallerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Installer::isInstalled()) {
            return;
        }

        // No database yet — use drivers that need none.
        config([
            'session.driver' => 'file',
            'cache.default' => 'array',
            'queue.default' => 'sync',
        ]);

        // The encrypter (CSRF + session cookies the wizard itself uses) needs
        // APP_KEY. On a fresh deploy it's empty — generate and persist it now,
        // before the encrypter is resolved.
        if (empty(config('app.key'))) {
            $key = 'base64:'.base64_encode(random_bytes(32));
            config(['app.key' => $key]);

            try {
                (new EnvWriter)->write(['APP_KEY' => $key]);
            } catch (\Throwable $e) {
                // .env not writable yet — the requirements screen flags this.
            }
        }
    }
}
