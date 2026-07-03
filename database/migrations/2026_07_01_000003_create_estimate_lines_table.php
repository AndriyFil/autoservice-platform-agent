<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('description');
            $table->decimal('quantity', 8, 2);
            $table->integer('unit_price_cents');
            $table->decimal('tax_rate', 5, 2);
            $table->integer('subtotal_cents');
            $table->integer('tax_cents');
            $table->integer('total_cents');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('estimate_id');
            $table->index(['estimate_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_lines');
    }
};
