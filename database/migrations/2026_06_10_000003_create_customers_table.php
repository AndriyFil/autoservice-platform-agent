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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('normalized_phone');
            $table->timestamps();

            $table->unique(['workshop_id', 'normalized_phone']);
            $table->index('workshop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
