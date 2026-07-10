<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dev/portfolio cleanup: estimated belongs to Estimate, not the RepairOrder workflow.
        DB::table('repair_orders')
            ->where('status', 'estimated')
            ->update(['status' => 'draft']);

        DB::statement(
            "ALTER TABLE repair_orders ADD CONSTRAINT repair_orders_status_check CHECK (status IN ('draft', 'in_progress', 'completed', 'cancelled'))",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE repair_orders DROP CONSTRAINT IF EXISTS repair_orders_status_check');
    }
};
