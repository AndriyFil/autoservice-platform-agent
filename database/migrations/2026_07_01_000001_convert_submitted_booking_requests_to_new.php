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
        DB::table('booking_requests')
            ->where('status', 'submitted')
            ->update(['status' => 'new']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally irreversible: "new" is now the canonical initial status.
    }
};
