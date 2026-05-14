<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tables created by the legacy Laravel Passport package — no longer used. */
    private array $tables = [
        'oauth_access_tokens',
        'oauth_auth_codes',
        'oauth_clients',
        'oauth_personal_access_clients',
        'oauth_refresh_tokens',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable FK checks so we can drop in any order
        Schema::disableForeignKeyConstraints();

        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreating Passport tables is handled by the original Passport migrations.
        // If you ever re-add laravel/passport, simply roll back those migrations instead.
    }
};
