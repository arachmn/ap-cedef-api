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
        Schema::create('payment_voucher_invoices', function (Blueprint $table) {
            $table->id();
            $table->integer('pv_id');
            $table->integer('inv_id');
            $table->string('inv_number');
            $table->decimal('ppn_amount', 12, 2);
            $table->decimal('pph_amount', 12, 2);
            $table->decimal('dpp_amount', 12, 2);
            $table->decimal('inv_amount', 12, 2);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_voucher_invoices');
    }
};
