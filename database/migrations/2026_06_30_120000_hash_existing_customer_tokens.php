<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert any existing plaintext customer tokens to their SHA-256 hash so the
     * column matches the new hashed-token scheme (see Customer::hashToken). Tokens
     * were UUIDs (36 chars) while a hash is 64 hex chars, so we only convert rows
     * that aren't already hashed. Clients keep their existing plaintext token — it
     * now resolves via its hash in ResolveCustomerByToken.
     */
    public function up(): void
    {
        DB::table('customers')
            ->whereNotNull('token')
            ->where('token', '<>', '')
            ->whereRaw('CHAR_LENGTH(token) <> 64')
            ->update(['token' => DB::raw('SHA2(token, 256)')]);
    }

    /**
     * Irreversible — a hash cannot be turned back into its plaintext token.
     */
    public function down(): void
    {
        // no-op: hashing is one-way.
    }
};
