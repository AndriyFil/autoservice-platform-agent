<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table): void {
            $table->boolean('requires_estimate_approval')->default(true)->after('status');
        });

        Schema::table('estimates', function (Blueprint $table): void {
            $table->boolean('requires_customer_approval')->default(false)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table): void {
            $table->dropColumn('requires_customer_approval');
        });

        Schema::table('repair_orders', function (Blueprint $table): void {
            $table->dropColumn('requires_estimate_approval');
        });
    }
};
