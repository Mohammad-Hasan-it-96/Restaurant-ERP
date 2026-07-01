<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * NOTE: the install wizard (InstallController) does NOT call this seeder — it
     * creates the admin from operator input and runs the sub-seeders individually.
     * This class exists for local/dev bootstrapping and manual re-seeding only.
     */
    public function run(): void
    {
        $this->seedAdmin();

        $this->call([
            SystemConfigSeeder::class,
            LanguageSeeder::class,
            DeliveryZoneSeeder::class,
        ]);
    }

    /**
     * Create the bootstrap admin — WITHOUT ever planting a known-credential
     * backdoor. In production this is skipped unless explicitly forced, and the
     * password always comes from env or is randomly generated (never a literal).
     */
    private function seedAdmin(): void
    {
        if (app()->environment('production') && ! filter_var(env('ALLOW_PROD_SEED', false), FILTER_VALIDATE_BOOL)) {
            $this->command?->warn(
                'DatabaseSeeder: skipping admin creation in production. Use the install '
                .'wizard, or set ALLOW_PROD_SEED=true (with ADMIN_PASSWORD) to force.'
            );

            return;
        }

        $email = (string) env('ADMIN_EMAIL', 'admin@example.com');
        $password = (string) env('ADMIN_PASSWORD', '');
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(20);
        }

        // Idempotent; role is not mass-assignable, so assign it explicitly. The
        // model's 'hashed' password cast hashes the plaintext on save (do NOT
        // pre-hash here or it double-hashes).
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $user->name ?: 'Super Admin';
        $user->password = $password;
        $user->role = 'admin';
        $user->save();

        if ($generated) {
            $this->command?->warn("DatabaseSeeder: created/updated admin {$email}");
            $this->command?->warn("Generated password (store it now — not recoverable): {$password}");
        }
    }
}
