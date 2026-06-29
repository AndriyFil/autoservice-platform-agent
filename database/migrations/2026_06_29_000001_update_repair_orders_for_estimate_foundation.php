<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();
            $table->foreignId('vehicle_id')->nullable()->change();
            $table->text('problem_description')->nullable()->change();
            $table->string('status')->default('draft')->change();
            $table->text('notes')->nullable()->after('status');
            $table->foreignId('created_by_user_id')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });

        DB::table('repair_orders')
            ->where('status', 'open')
            ->update(['status' => 'draft']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('repair_orders')
            ->where('status', 'draft')
            ->update(['status' => 'open']);

        Schema::table('repair_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn('notes');
            $table->string('status')->default('open')->change();
            $table->text('problem_description')->nullable(false)->change();
            $table->foreignId('vehicle_id')->nullable()->change();
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
