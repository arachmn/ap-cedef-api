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
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->integer('vend_code');
            $table->string('pv_number')->unique();
            $table->string('dpp_account');
            $table->string('ppn_account');
            $table->string('pph_account');
            $table->integer('pv_status');
            $table->timestamp('pv_doc_date')->nullable();
            $table->timestamp('pv_due_date')->nullable();
            $table->integer('bank_id');
            $table->string('apvdh_code')->unique();
            $table->decimal('ppn_amount', 12, 2);
            $table->decimal('pph_amount', 12, 2);
            $table->decimal('dpp_amount', 12, 2);
            $table->decimal('pv_amount', 12, 2);
            $table->string('pv_note')->nullable();
            $table->text('pv_description');
            $table->integer('user_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
    }
};
