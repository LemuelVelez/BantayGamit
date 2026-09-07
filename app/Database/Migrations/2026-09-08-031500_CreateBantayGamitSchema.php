<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Compatibility marker for the duplicate schema migration that previously
 * redeclared CreateBantayGamitSchema. The actual schema remains owned by the
 * original 2026-09-03 migration so existing migration history stays valid.
 */
class ResolveDuplicateBantayGamitSchemaMigration extends Migration
{
    public function up(): void
    {
        // Intentionally empty. The schema is created by the 2026-09-03 migration.
    }

    public function down(): void
    {
        // Intentionally empty. The schema is reverted by the 2026-09-03 migration.
    }
}
