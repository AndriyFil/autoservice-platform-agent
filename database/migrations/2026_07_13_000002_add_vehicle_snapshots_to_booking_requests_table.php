<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('booking_requests')->whereNull('workshop_id')->exists()) {
            throw new RuntimeException(
                'Cannot enforce booking_requests.workshop_id NOT NULL because legacy unassigned booking requests exist.'
            );
        }

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->string('vehicle_brand')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->unsignedSmallInteger('vehicle_year')->nullable();
            $table->string('vehicle_license_plate')->nullable();
        });

        DB::statement('ALTER TABLE booking_requests ALTER COLUMN workshop_id SET NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE booking_requests ALTER COLUMN workshop_id DROP NOT NULL');

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_brand',
                'vehicle_model',
                'vehicle_year',
                'vehicle_license_plate',
            ]);
        });
    }
};
