<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\User;
use App\Services\SystemConfigService;
use App\Support\EnvWriter;
use App\Support\Installer;
use Database\Seeders\DeliveryZoneSeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\SystemConfigSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * InstallController — the install wizard.
 *
 * Steps store into the (file-driver) session and advance; finish() applies
 * everything and writes the lock. Reachable only while not installed (gated by
 * EnsureInstalled). See the "Installer Wizard" section in CLAUDE.md.
 */
class InstallController extends Controller
{
    public function index()
    {
        return view('installer.welcome', ['checks' => $this->requirements()]);
    }

    // ── Step 1: Database ────────────────────────────────────────────────────
    public function database()
    {
        return view('installer.database', ['data' => session('install.database', [])]);
    }

    public function storeDatabase(Request $request)
    {
        $data = $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        // Live connection test on a throwaway connection.
        config(['database.connections.install_test' => [
            'driver' => 'mysql',
            'host' => $data['db_host'],
            'port' => $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);

        try {
            DB::connection('install_test')->getPdo();
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Database connection failed: '.$e->getMessage());
        } finally {
            DB::purge('install_test');
        }

        session(['install.database' => $data]);

        return redirect()->route('install.app');
    }

    // ── Step 2: App URL ─────────────────────────────────────────────────────
    public function appUrl()
    {
        return view('installer.app', [
            'data' => session('install.app', ['app_url' => $this->guessUrl()]),
        ]);
    }

    public function storeApp(Request $request)
    {
        $data = $request->validate(['app_url' => 'required|url']);

        session(['install.app' => $data]);

        return redirect()->route('install.restaurant');
    }

    // ── Step 3: Restaurant info ─────────────────────────────────────────────
    public function restaurant()
    {
        return view('installer.restaurant', ['data' => session('install.restaurant', [])]);
    }

    public function storeRestaurant(Request $request)
    {
        $data = $request->validate([
            'restaurant_name_ar' => 'required|string|max:255',
            'restaurant_name_en' => 'nullable|string|max:255',
            'restaurant_phone' => 'required|string|max:50',
            'restaurant_whatsapp' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:2048',
            'timezone' => 'required|string|timezone',
            'default_language' => 'required|in:ar,en',
            'currency_code' => 'required|string|max:8',
            'currency_symbol' => 'required|string|max:8',
            'currency_position' => 'required|in:prefix,suffix',
            'currency_decimals' => 'required|integer|min:0|max:4',
            'theme_primary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($request->hasFile('logo')) {
            $data['restaurant_logo'] = $request->file('logo')->store('logos', 'public');
        }
        unset($data['logo']);

        session(['install.restaurant' => $data]);

        return redirect()->route('install.admin');
    }

    // ── Step 4: Admin account ───────────────────────────────────────────────
    public function admin()
    {
        return view('installer.admin', ['data' => session('install.admin', [])]);
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        session(['install.admin' => $data]);

        return redirect()->route('install.finish');
    }

    // ── Step 5: Finish (apply everything) ───────────────────────────────────
    public function finish()
    {
        foreach (['database', 'app', 'restaurant', 'admin'] as $step) {
            if (! session("install.$step")) {
                return redirect()->route('install.'.$step);
            }
        }

        $db = session('install.database');
        $app = session('install.app');
        $restaurant = session('install.restaurant');
        $admin = session('install.admin');

        try {
            // 1. Persist .env (DB + app + timezone + brand color), eliminating manual editing.
            (new EnvWriter)->write([
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => $app['app_url'],
                'APP_TIMEZONE' => $restaurant['timezone'],
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $db['db_host'],
                'DB_PORT' => $db['db_port'],
                'DB_DATABASE' => $db['db_database'],
                'DB_USERNAME' => $db['db_username'],
                'DB_PASSWORD' => $db['db_password'] ?? '',
                'THEME_PRIMARY' => $restaurant['theme_primary'],
            ]);

            // 2. Point the live process at the new DB so migrations run now.
            config(['database.connections.mysql' => array_merge(
                config('database.connections.mysql'),
                [
                    'host' => $db['db_host'],
                    'port' => $db['db_port'],
                    'database' => $db['db_database'],
                    'username' => $db['db_username'],
                    'password' => $db['db_password'] ?? '',
                ]
            )]);
            DB::purge('mysql');
            DB::reconnect('mysql');

            // 3. Schema + default data — NOT DatabaseSeeder (it hardcodes an admin).
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--class' => SystemConfigSeeder::class, '--force' => true]);
            Artisan::call('db:seed', ['--class' => LanguageSeeder::class, '--force' => true]);
            Artisan::call('db:seed', ['--class' => DeliveryZoneSeeder::class, '--force' => true]);

            // 4. Make the uploaded logo web-accessible (ignore if link exists).
            try {
                Artisan::call('storage:link');
            } catch (\Throwable $e) {
                // symlink already present, or unsupported — non-fatal.
            }

            // 5. Admin from input (password cast auto-hashes). Idempotent on retry.
            // role is not mass-assignable, so assign it explicitly.
            $adminUser = User::firstOrNew(['email' => $admin['email']]);
            $adminUser->name = $admin['name'];
            $adminUser->password = $admin['password'];
            $adminUser->role = 'admin';
            $adminUser->save();

            // 6. Restaurant info overrides the seeded defaults.
            $config = app(SystemConfigService::class);
            $config->set('restaurant_name_ar', $restaurant['restaurant_name_ar'], 'general');
            $config->set('site_name', $restaurant['restaurant_name_ar'], 'general');
            if (! empty($restaurant['restaurant_name_en'])) {
                $config->set('restaurant_name_en', $restaurant['restaurant_name_en'], 'general');
            }
            $config->set('restaurant_phone', $restaurant['restaurant_phone'], 'restaurant');
            if (! empty($restaurant['restaurant_whatsapp'])) {
                $config->set('restaurant_whatsapp', $restaurant['restaurant_whatsapp'], 'restaurant');
            }
            if (! empty($restaurant['restaurant_logo'])) {
                $config->set('restaurant_logo', $restaurant['restaurant_logo'], 'restaurant');
            }

            // 6b. Currency (group general) — overrides the generic seeded defaults.
            $config->set('currency_code', $restaurant['currency_code'], 'general');
            $config->set('currency_symbol', $restaurant['currency_symbol'], 'general');
            $config->set('currency_position', $restaurant['currency_position'], 'general');
            $config->set('currency_decimals', (string) $restaurant['currency_decimals'], 'general');

            // 6c. Default language — flip the chosen one on, the rest off.
            Language::query()->update(['is_default' => 0]);
            Language::query()->where('code', $restaurant['default_language'])->update(['is_default' => 1]);

            // 7. Lock, clear wizard state + caches.
            Installer::lock();
            session()->forget('install');
            Artisan::call('config:clear');
            Artisan::call('route:clear');

            return redirect()->route('auth.login')->with('success', 'Setup complete — please sign in.');
        } catch (\Throwable $e) {
            return redirect()->route('install.index')->with('error', 'Installation failed: '.$e->getMessage());
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    private function guessUrl(): string
    {
        return request()->getSchemeAndHttpHost();
    }

    /**
     * @return array<string, bool>
     */
    private function requirements(): array
    {
        $ext = fn (string $e) => extension_loaded($e);

        return [
            'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'pdo_mysql extension' => $ext('pdo_mysql'),
            'mbstring extension' => $ext('mbstring'),
            'openssl extension' => $ext('openssl'),
            'gd extension' => $ext('gd'),
            'zip extension' => $ext('zip'),
            'fileinfo extension' => $ext('fileinfo'),
            'ctype extension' => $ext('ctype'),
            'json extension' => $ext('json'),
            '.env writable' => is_writable(base_path('.env')) || is_writable(base_path()),
            'storage/ writable' => is_writable(storage_path()),
        ];
    }
}
