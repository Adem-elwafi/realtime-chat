<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record the InnoDB conversion (run directly on 2026-07-28).
     *
     * The entire database was migrated from MyISAM to InnoDB to enable
     * foreign key enforcement. This migration is a no-op placeholder
     * so the change is tracked in version control.
     */
    public function up(): void
    {
        // Conversion was executed directly via DB::statement — see audit log.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema-only rollback is not provided as reverting to MyISAM
        // would break FK enforcement.
    }
};
