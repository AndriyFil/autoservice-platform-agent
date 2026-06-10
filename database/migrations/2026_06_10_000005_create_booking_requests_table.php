<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('problem_description');
            $table->date('preferred_date')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();

            $table->index('workshop_id');
            $table->index('customer_id');
            $table->index('vehicle_id');
            $table->index('created_by_user_id');
            $table->index(['workshop_id', 'status']);
            $table->index(['workshop_id', 'created_at']);
            $table->index(['workshop_id', 'preferred_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
