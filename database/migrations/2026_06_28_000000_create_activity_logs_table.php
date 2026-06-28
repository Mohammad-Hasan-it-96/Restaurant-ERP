<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business audit trail. Records who did what to which entity, for curated
 * business events only (see App\Services\ActivityLogger). Deliberately separate
 * from the file-based application logs (App\Services\LogService).
 *
 * Polymorphic causer/subject columns carry NO foreign keys, so a row survives
 * even after its subject (e.g. a deleted product) or causer is removed; the
 * denormalised *_label columns keep the row human-readable afterwards. Rows are
 * immutable — created_at only, no updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Actor: User (admin/moderator) | Customer | null (system). No FK.
            $table->nullableMorphs('causer');
            $table->string('causer_label')->nullable();

            // Subject of the action. No FK → survives subject deletion.
            $table->nullableMorphs('subject');
            $table->string('subject_label')->nullable();

            $table->string('action')->index();        // e.g. order.accepted
            $table->string('description')->nullable(); // human-readable summary
            $table->json('properties')->nullable();    // {from, to, reason, ...}

            $table->string('ip_address', 45)->nullable();
            $table->string('request_id')->nullable();  // correlate to X-Request-Id logs

            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
