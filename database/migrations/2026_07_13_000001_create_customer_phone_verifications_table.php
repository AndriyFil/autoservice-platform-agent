<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone_normalized');
            $table->string('code_hash');
            $table->timestampTz('expires_at');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('invalidated_at')->nullable();
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();

            $table->index(['phone_normalized', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_phone_verifications');
    }
};
